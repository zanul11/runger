<?php

namespace App\Http\Controllers\Api\Timing;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\RosterArea;
use App\Models\RosterConsumptionSetting;
use App\Models\RosterGroup;
use App\Models\RosterPosition;
use App\Services\RosterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Roster panitia hari H (GTR Crew → Roster).
 * Semua akun GTR Crew: lihat & export. Admin + panitia (koordinator): isi petugas, tamu konsumsi,
 * import petugas. Import struktur & ganti nama area/kelompok/pos: admin. Struktur lengkap dikelola di
 * Filament (Roster Panitia → Struktur Roster).
 * Versi publik baca-saja: /crew/roster (App\Http\Controllers\RosterPublicController).
 */
class RosterController extends Controller
{
    public function __construct(private RosterService $roster) {}

    /** GET /roster?event= */
    public function index(Request $request): JsonResponse
    {
        $event = $this->event($request);
        if (! $event) {
            return response()->json(['message' => 'Belum ada event untuk roster.'], 404);
        }

        return response()->json($this->roster->payload($event, $request->user()));
    }

    /** PUT /roster/positions/{position}/slots/{slot} — Body: nama?, no_hp?, catatan? (field yang dikirim saja). */
    public function slot(Request $request, RosterPosition $position, int $slot): JsonResponse
    {
        $data = $request->validate([
            'nama' => ['sometimes', 'nullable', 'string', 'max:120'],
            'no_hp' => ['sometimes', 'nullable', 'string', 'max:32', 'regex:/^[0-9+()\-\s.]*$/'],
            'catatan' => ['sometimes', 'nullable', 'string', 'max:255'],
        ], ['no_hp.regex' => 'No. HP hanya boleh angka, spasi, +, -, ( ).']);

        $row = $this->roster->saveSlot($position, $slot, $data, $request->user());

        return response()->json(['slot' => $this->roster->slotPayload($row)]);
    }

    /**
     * PATCH /roster/{type}/{id} (admin) — ubah dari GTR Crew tanpa membuka panel admin.
     *  - areas / groups: Body nama. Slug area tidak berubah (dipakai acuan konsumsi).
     *  - positions: Body (field yang dikirim saja) nama, deskripsi (jobdesk), min_petugas,
     *    max_petugas, compact (bool). Bila maks dikurangi padahal ada petugas di slot yang akan
     *    hilang → 422 + `can_compact`; kirim ulang dengan compact=true untuk memindahkan mereka
     *    ke slot kosong lebih dulu.
     */
    public function update(Request $request, string $type, int $id): JsonResponse
    {
        if ($type !== 'positions') {
            $model = $type === 'areas' ? RosterArea::findOrFail($id) : RosterGroup::findOrFail($id);
            $model->update(['nama' => trim($request->validate(['nama' => ['required', 'string', 'max:80']])['nama'])]);

            return response()->json(['id' => $model->id, 'nama' => $model->nama, 'message' => 'Nama diganti.']);
        }

        $pos = RosterPosition::findOrFail($id);
        $data = $request->validate([
            'nama' => ['sometimes', 'required', 'string', 'max:120'],
            'deskripsi' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'min_petugas' => ['sometimes', 'required', 'integer', 'min:0', 'max:50'],
            'max_petugas' => ['sometimes', 'required', 'integer', 'min:1', 'max:50'],
            'compact' => ['sometimes', 'boolean'],
        ], [
            'min_petugas.min' => 'Minimal tidak boleh kurang dari 0.',
            'max_petugas.min' => 'Maksimal paling sedikit 1 orang.',
        ]);
        $min = (int) ($data['min_petugas'] ?? $pos->min_petugas);
        $max = (int) ($data['max_petugas'] ?? $pos->max_petugas);
        if ($max < $min) {
            throw ValidationException::withMessages(['max_petugas' => 'Maksimal harus ≥ minimal.']);
        }

        if ($max < $pos->max_petugas) {
            if (! empty($data['compact'])) {
                $this->roster->compactSlots($pos, $max);
            }
            if ($info = $this->roster->maxChangeInfo($pos, $max)) {
                $msg = "Ada {$info['count']} petugas di slot yang akan dihapus (slot ".implode(', ', $info['slots']).').'
                    .($info['fits'] ? '' : ' Slot kosong tidak cukup — kosongkan dulu sebagian petugas.');

                return response()->json(['message' => $msg, 'errors' => ['max_petugas' => [$msg]], 'can_compact' => $info['fits']], 422);
            }
        }

        $pos->update(collect($data)->except('compact')
            ->map(fn ($v, $k) => $k === 'deskripsi' ? (trim((string) $v) ?: null) : (is_string($v) ? trim($v) : $v))
            ->all());

        return response()->json(['id' => $pos->id, 'nama' => $pos->nama, 'message' => 'Pos diperbarui.']);
    }

    /** PUT /roster/consumption — Body: tamu_tambahan. */
    public function consumption(Request $request): JsonResponse
    {
        $event = $this->event($request) ?? abort(404);
        $data = $request->validate(['tamu_tambahan' => ['required', 'integer', 'min:0', 'max:5000']]);
        $setting = RosterConsumptionSetting::forEvent($event);
        $setting->update($data);

        return response()->json(['tamu_tambahan' => $setting->tamu_tambahan]);
    }

    /** GET /roster/export */
    public function export(Request $request): JsonResponse
    {
        $event = $this->event($request) ?? abort(404);

        return response()->json($this->roster->export($event));
    }

    /** POST /roster/import/preview & /roster/import — Body: mode (petugas|struktur), data (isi file export). */
    public function import(Request $request): JsonResponse
    {
        $event = $this->event($request) ?? abort(404);
        $input = $request->validate([
            'mode' => ['required', Rule::in(['petugas', 'struktur'])],
            'data' => ['required', 'array'],
        ]);
        abort_if($input['mode'] === 'struktur' && ! $request->user()->isAdmin(), 403, 'Import struktur hanya untuk admin.');

        $dryRun = $request->routeIs('timing.roster.import.preview');
        $result = $this->roster->import($event, $input['data'], $input['mode'], $request->user(), $dryRun);
        $n = count($result['changes']);

        return response()->json($result + [
            'applied' => ! $dryRun,
            'message' => $dryRun
                ? ($n ? "{$n} perubahan akan disimpan." : 'Tidak ada perubahan.')
                : ($n ? "{$n} perubahan disimpan." : 'Tidak ada perubahan.'),
        ]);
    }

    private function event(Request $request): ?Event
    {
        return $request->filled('event') ? Event::find($request->integer('event')) : Event::rosterActive();
    }
}
