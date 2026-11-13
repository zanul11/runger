<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrResult;
use App\Models\GtrResultAdjustment;
use App\Models\GtrScanLog;
use App\Models\GtrSetting;
use App\Models\GtrTimingPoint;
use App\Services\GtrRaceDayReset;
use App\Services\GtrTimingAdmin;
use App\Services\GtrTimingSession;
use App\Services\MarshalService;
use App\Services\LiveTracker;
use App\Support\Bib;
use App\Support\GtrRaceDayStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Menu "Kontrol" scanner untuk admin (race day dari HP): START race, status pos & device,
 * sweep, lacak peserta, finish board, scan bermasalah, dan reset data uji.
 * Semua waktu dikirim sebagai epoch ms; scanner memformat ke WITA.
 */
class AdminController extends Controller
{
    /** Alasan "ignored" yang perlu ditindak admin (bukan duplikat / dibatalkan marshal). */
    private const ACTIONABLE_REASONS = [GtrScanLog::REASON_UNKNOWN_BIB, GtrScanLog::REASON_INVALID_BIB, GtrScanLog::REASON_WRONG_CATEGORY];

    public function __construct(private GtrTimingAdmin $admin) {}

    // ------------------------------------------------------------- START ----

    /** GET /admin/race — status start + ringkasan hasil. */
    public function race(): JsonResponse
    {
        return response()->json($this->racePayload());
    }

    /** POST /admin/start — START bersama semua kategori aktif. Body: fired_at_ms? (jam terkoreksi HP). */
    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fired_at_ms' => ['nullable', 'integer', 'min:1577836800000', 'max:'.(DateTimeMs::toMs(now()) + 60_000)],
        ]);
        $at = ! empty($data['fired_at_ms']) ? DateTimeMs::toCarbon((int) $data['fired_at_ms']) : null;

        $fired = $this->admin->fireGunAll($request->user(), $at);

        return response()->json([
            'fired' => $fired,
            'message' => $fired ? 'START tercatat.' : 'Race sudah di-start. Pakai Koreksi untuk mengubah jam.',
        ] + $this->racePayload(), $fired ? 200 : 409);
    }

    /** POST /admin/start/correct — koreksi gun time. Body: time "HH:MM:SS(.mmm)" WITA, di tanggal gun time / jadwal start. */
    public function correct(Request $request): JsonResponse
    {
        $data = $request->validate([
            'time' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2}(\.\d{1,3})?)?$/'],
        ]);

        $at = $this->raceDayTime($data['time']);

        if ($at->greaterThan(now()->addMinute())) {
            return response()->json(['message' => 'Jam start tidak boleh di masa depan.'], 422);
        }

        $this->admin->fireGunAll($request->user(), $at, force: true);

        return response()->json(['fired' => true, 'message' => 'Gun time dikoreksi ke '.$at->format('H:i:s.v').'.'] + $this->racePayload());
    }

    private function racePayload(): array
    {
        $cats = GtrCategory::with('gunFiredBy:id,name')->where('is_active', true)
            ->withCount(['registrations as eligible_count' => fn ($q) => $q->raceEligible()])
            ->orderBy('sort_order')->orderBy('id')->get();
        $status = GtrResult::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'categories' => $cats->map(fn (GtrCategory $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'eligible' => (int) $c->eligible_count,
                'gun_start_ms' => DateTimeMs::toMs($c->gun_start),
                'gun_fired_at_ms' => DateTimeMs::toMs($c->gun_fired_at),
                'gun_fired_by' => $c->gunFiredBy?->name,
            ])->values(),
            'fired_at_ms' => DateTimeMs::toMs($cats->pluck('gun_fired_at')->filter()->first()),
            'results' => [
                'finisher' => (int) ($status[GtrResult::STATUS_FINISHER] ?? 0),
                'dnf' => (int) ($status[GtrResult::STATUS_DNF] ?? 0),
                'dq' => (int) ($status[GtrResult::STATUS_DQ] ?? 0),
                'review' => GtrResult::where('needs_review', true)->count(),
            ],
            'issues' => $this->issueCount(),
            'results_published' => (bool) GtrSetting::first()?->results_published,
            'server_time_ms' => DateTimeMs::toMs(now()),
        ] + $this->alerts($cats->pluck('gun_fired_at')->filter()->isNotEmpty());
    }

    /**
     * Peringatan di beranda Kontrol + angka penanda tile. Sebelum START: checklist kesiapan;
     * setelah START: HP marshal diam, peserta belum lewat CP terakhir, scan bermasalah.
     *
     * @return array{alerts: list<array{level: string, view: string, text: string}>, badges: array<string,int>}
     */
    private function alerts(bool $fired): array
    {
        $alerts = [];
        $badges = ['checklist' => 0, 'posts' => 0, 'sweep' => 0, 'issues' => $this->issueCount()];

        if ($fired) {
            $states = collect(GtrRaceDayStats::devices())->countBy('state');
            if ($n = (int) ($states['stale'] ?? 0)) {
                $alerts[] = ['level' => 'bad', 'view' => 'posts', 'text' => "{$n} HP marshal tidak mengirim data ≥ ".GtrRaceDayStats::STALE_MIN.' menit'];
            }
            if ($n = (int) ($states['quiet'] ?? 0)) {
                $alerts[] = ['level' => 'warn', 'view' => 'posts', 'text' => "{$n} HP marshal diam ≥ ".GtrRaceDayStats::QUIET_MIN.' menit'];
            }
            $badges['posts'] = (int) ($states['stale'] ?? 0) + (int) ($states['quiet'] ?? 0);

            $badges['sweep'] = array_sum(array_column(GtrRaceDayStats::notSeenAtLastCp(), 'on_course'));
            if ($badges['sweep']) {
                $alerts[] = ['level' => 'warn', 'view' => 'sweep', 'text' => "{$badges['sweep']} peserta di lintasan belum lewat CP terakhir"];
            }
        } else {
            $badges['checklist'] = collect($this->checklistItems())->where('state', 'warn')->count();
            if ($badges['checklist']) {
                $alerts[] = ['level' => 'warn', 'view' => 'checklist', 'text' => "Checklist kesiapan: {$badges['checklist']} perlu dicek"];
            }
        }

        if ($badges['issues']) {
            $alerts[] = ['level' => 'warn', 'view' => 'issues', 'text' => "{$badges['issues']} scan / hasil perlu dicek"];
        }

        return ['alerts' => $alerts, 'badges' => $badges];
    }

    // --------------------------------------------------------- checklist ----

    /** GET /admin/checklist — kesiapan sebelum START. */
    public function checklist(): JsonResponse
    {
        return response()->json(['items' => $this->checklistItems(), 'server_time_ms' => DateTimeMs::toMs(now())]);
    }

    /** @return list<array{key: string, label: string, state: 'ok'|'warn'|'info', detail: string}> */
    private function checklistItems(): array
    {
        $tz = config('app.timezone');
        $items = [];
        $cats = GtrCategory::where('is_active', true)
            ->withCount(['registrations as eligible_count' => fn ($q) => $q->raceEligible()])
            ->orderBy('sort_order')->orderBy('id')->get();

        // 1. Peserta: lunas + BIB.
        $noBib = GtrRegistration::where('payment_status', 'paid')
            ->where(fn ($q) => $q->whereNull('bib_number')->orWhere('bib_number', ''))->count();
        $items[] = [
            'key' => 'participants', 'label' => 'Peserta siap race', 'state' => $noBib ? 'warn' : 'ok',
            'detail' => $cats->map(fn ($c) => "{$c->name} {$c->eligible_count}")->join(' · ')
                .($noBib ? " · {$noBib} sudah lunas tapi belum punya BIB (jalankan gtr:assign-bibs)" : ''),
        ];

        // 2. Jadwal start.
        $noSchedule = $cats->filter(fn ($c) => ! $c->gun_start)->pluck('name');
        $schedule = $cats->pluck('gun_start')->filter()->sort()->first();
        $items[] = [
            'key' => 'schedule', 'label' => 'Jadwal start', 'state' => $noSchedule->isEmpty() ? 'ok' : 'warn',
            'detail' => $noSchedule->isEmpty()
                ? 'Start '.Carbon::parse($schedule)->setTimezone($tz)->translatedFormat('j M Y H:i').' WITA'
                : 'Jadwal start belum diisi: '.$noSchedule->join(', '),
        ];

        // 3. Marshal per pos & 4. HP marshal sudah login hari ini.
        $points = GtrTimingPoint::with(['categories:id', 'assignments' => fn ($q) => $q->where('is_active', true)->with('user:id,name,role')])
            ->where('type', '!=', GtrTimingPoint::TYPE_START)->orderBy('sort_order')->get()
            ->filter(fn ($p) => $p->categories->isNotEmpty());
        $marshalOf = fn ($p) => $p->assignments->filter(fn ($a) => $a->user?->role === \App\Models\User::ROLE_MARSHAL);
        $noMarshal = $points->filter(fn ($p) => $marshalOf($p)->isEmpty())->pluck('code');
        $items[] = [
            'key' => 'marshals', 'label' => 'Marshal di setiap pos', 'state' => $noMarshal->isEmpty() ? 'ok' : 'warn',
            'detail' => $noMarshal->isEmpty() ? 'Semua '.$points->count().' pos punya marshal' : 'Belum ada marshal: '.$noMarshal->join(', '),
        ];

        $since = now()->subHours(12);
        $notLogged = $points->flatMap(fn ($p) => $marshalOf($p)
            ->filter(fn ($a) => ! $a->device_seen_at || $a->device_seen_at->lt($since))
            ->map(fn ($a) => "{$a->user->name} ({$p->code})"));
        $items[] = [
            'key' => 'logins', 'label' => 'HP marshal sudah login', 'state' => $notLogged->isEmpty() ? 'ok' : 'warn',
            'detail' => $notLogged->isEmpty() ? 'Semua HP marshal aktif dalam 12 jam terakhir' : 'Belum login / buka scanner: '.$notLogged->join(', '),
        ];

        // 5. Data uji bersih & START.
        $firedAt = $cats->pluck('gun_fired_at')->filter()->first();
        $scans = GtrScanLog::count();
        $scheduleDay = $schedule ? Carbon::parse($schedule)->setTimezone($tz)->toDateString() : null;
        [$state, $detail] = match (true) {
            $firedAt && $scheduleDay && Carbon::parse($firedAt)->setTimezone($tz)->toDateString() !== $scheduleDay
                => ['warn', 'START tercatat '.Carbon::parse($firedAt)->setTimezone($tz)->translatedFormat('j M H:i').' — bukan hari race. Data uji? Reset dulu.'],
            (bool) $firedAt => ['ok', 'Race sudah START '.Carbon::parse($firedAt)->setTimezone($tz)->format('H:i:s')],
            $scans > 0 => ['warn', "Ada {$scans} scan sebelum START — sisa uji coba? Reset data uji."],
            default => ['ok', 'Belum ada scan & belum START'],
        };
        $items[] = ['key' => 'clean', 'label' => 'Data uji sudah bersih', 'state' => $state, 'detail' => $detail];

        // 6. Hasil belum dipublikasikan.
        $published = (bool) GtrSetting::first()?->results_published;
        $items[] = [
            'key' => 'published', 'label' => 'Hasil belum dipublikasikan', 'state' => $published && ! $firedAt ? 'warn' : 'ok',
            'detail' => $published ? 'Hasil sedang tampil di website' : 'Hasil belum tampil di website',
        ];

        // 7. Racepack (info).
        $eligible = (int) $cats->sum('eligible_count');
        $taken = GtrRegistration::query()->raceEligible()->whereNotNull('racepack_collected_at')->count();
        $items[] = [
            'key' => 'racepack', 'label' => 'Racepack diambil', 'state' => $taken >= $eligible ? 'ok' : 'info',
            'detail' => "{$taken} dari {$eligible} peserta".($taken < $eligible ? ' · '.($eligible - $taken).' belum ambil' : ''),
        ];

        return $items;
    }

    // -------------------------------------------------------- status pos ----

    /** GET /admin/posts — peserta tercatat per pos × kategori + device marshal. */
    public function posts(): JsonResponse
    {
        $m = GtrRaceDayStats::postMatrix();
        $cats = $m['categories'];

        return response()->json([
            'categories' => $cats->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
            'rows' => array_map(fn ($r) => [
                'id' => $r['id'],
                'code' => $r['code'],
                'name' => $r['name'],
                'type' => $r['type'],
                'marshals' => $r['marshals'],
                'last_at_ms' => DateTimeMs::toMs($r['last_at']),
                'cells' => $cats->map(fn ($c) => $r['cells'][$c->id] === null ? null : ['category_id' => $c->id] + $r['cells'][$c->id])->values(),
            ], $m['rows']),
            'devices' => array_map(fn ($d) => [
                'device_id' => substr((string) $d['device_id'], 0, 8),
                'user' => $d['user'],
                'point' => $d['point'],
                'total' => $d['total'],
                'last_received_ms' => DateTimeMs::toMs($d['last_received']),
                'last_scanned_ms' => DateTimeMs::toMs($d['last_scanned']),
                'state' => $d['state'],
            ], GtrRaceDayStats::devices()),
            'server_time_ms' => DateTimeMs::toMs(now()),
        ]);
    }

    // ------------------------------------------------------------- sweep ----

    /** GET /admin/sweep — peserta di lintasan yang belum terlihat di CP terakhir. */
    public function sweep(): JsonResponse
    {
        return response()->json([
            'categories' => array_map(fn ($c) => array_merge($c, [
                'rows' => array_map(fn ($r) => array_merge($r, [
                    'bib' => Bib::normalize($r['bib']) ?? (string) $r['bib'],
                    'last_at' => null,
                    'last_at_ms' => DateTimeMs::toMs($r['last_at']),
                ]), $c['rows']),
            ]), GtrRaceDayStats::notSeenAtLastCp()),
            'server_time_ms' => DateTimeMs::toMs(now()),
        ]);
    }

    // ------------------------------------------------------ lacak peserta ----

    /** GET /admin/participants?q= — cari BIB / nama (maks 30). */
    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 1) {
            return response()->json(['participants' => []]);
        }
        $bib = ctype_digit($q) ? Bib::normalize($q) : null;

        $regs = GtrRegistration::query()->raceEligible()
            ->with(['category:id,name', 'result:id,gtr_registration_id,status,finish_time'])
            ->where(fn ($w) => $bib !== null
                ? $w->where('bib_number', 'like', $bib.'%')
                : $w->where('full_name', 'like', '%'.$q.'%'))
            ->orderBy('bib_number')->limit(30)
            ->get(['id', 'gtr_category_id', 'bib_number', 'full_name', 'gender', 'race_status']);

        $last = $this->lastSeen($regs->pluck('id'));
        $manual = GtrRaceDayStats::manualStatuses($regs->pluck('id'));

        return response()->json(['participants' => $regs->map(fn (GtrRegistration $r) => [
            'bib' => Bib::normalize($r->bib_number) ?? (string) $r->bib_number,
            'name' => $r->full_name,
            'category' => $r->category?->name,
            'gender' => $r->genderCode(),
            'status' => $this->liveStatus($r, $last->get($r->id), $manual[$r->id] ?? null),
            'last_point' => $last->get($r->id)?->timingPoint?->code,
            'last_at_ms' => DateTimeMs::toMs($last->get($r->id)?->scanned_at),
        ])->values()]);
    }

    /** GET /admin/participants/{bib} — timeline per pos, hasil, kontak darurat, dan semua scan. */
    public function participant(string $bib, LiveTracker $tracker): JsonResponse
    {
        $reg = GtrRegistration::findByBib($bib);
        abort_unless($reg, 404, 'BIB tidak terdaftar / belum lunas.');
        $reg->load(['category', 'result', 'racepackCollector:id,name']);

        $scans = $reg->scanLogs()->with(['timingPoint:id,code,name', 'scanner:id,name'])
            ->orderBy('scanned_at')->get();
        $adjustments = GtrResultAdjustment::where('gtr_registration_id', $reg->id)
            ->where('is_active', true)->with('user:id,name')->latest()->get();
        $result = $reg->result;
        $manual = $adjustments->firstWhere('field', GtrResultAdjustment::FIELD_STATUS);
        $track = $tracker->build($reg);
        $track['status'] = $this->liveStatus($reg, $scans->first(fn ($s) => ! $s->is_ignored), $manual?->new_value, $track['status']);

        return response()->json([
            'track' => $track,
            'manual_status' => $manual ? [
                'status' => $manual->new_value,
                'reason' => $manual->reason,
                'by' => $manual->user?->name,
                'at_ms' => DateTimeMs::toMs($manual->created_at),
            ] : null,
            'bib' => Bib::normalize($reg->bib_number) ?? (string) $reg->bib_number,
            'gender' => $reg->genderCode(),
            'contact' => [
                'phone' => $reg->whatsapp,
                'emergency_name' => $reg->emergency_name,
                'emergency_phone' => $reg->emergency_contact,
                'blood_type' => $reg->blood_type,
            ],
            'racepack' => [
                'collected' => $reg->racepack_collected_at !== null,
                'at_ms' => DateTimeMs::toMs($reg->racepack_collected_at),
                'by' => $reg->racepackCollector?->name,
                'picked_by' => $reg->racepack_picked_by,
                'size' => $reg->size,
            ],
            'result' => $result ? [
                'status' => $result->status,
                'gun_time_ms' => $result->gun_time_ms,
                'net_time_ms' => $result->net_time_ms,
                'rank_overall' => $result->rank_overall,
                'rank_gender' => $result->rank_gender,
                'needs_review' => $result->needs_review,
                'review_note' => $result->review_note,
            ] : null,
            'adjustments' => $adjustments->map(fn ($a) => [
                'field' => $a->field, 'old' => $a->old_value, 'new' => $a->new_value, 'reason' => $a->reason, 'by' => $a->user?->name,
            ])->values(),
            'scans' => $scans->map(fn (GtrScanLog $s) => $this->scanRow($s))->values(),
        ]);
    }

    /**
     * POST /admin/participants/{bib}/status — tetapkan status (mundur / dievakuasi / DQ) dengan
     * alasan, atau "auto" = cabut status manual (kembali ke hitungan scan). Tercatat sebagai
     * koreksi hasil (audit) dan langsung mengeluarkan peserta dari daftar Sweep.
     */
    public function setStatus(Request $request, string $bib): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:dnf,dq,dns,finisher,auto'],
            'reason' => ['required_unless:status,auto', 'nullable', 'string', 'max:500'],
        ]);
        $reg = GtrRegistration::findByBib($bib);
        abort_unless($reg, 404, 'BIB tidak terdaftar / belum lunas.');

        if ($data['status'] === 'auto') {
            GtrResultAdjustment::where('gtr_registration_id', $reg->id)->where('field', GtrResultAdjustment::FIELD_STATUS)
                ->where('is_active', true)->get()
                ->each(fn ($a) => $this->admin->deactivateAdjustment($a));

            return response()->json(['message' => 'Status manual dicabut — kembali mengikuti scan.']);
        }

        try {
            $this->admin->adjustResult($reg, GtrResultAdjustment::FIELD_STATUS, $data['status'], (string) $data['reason'], $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'BIB '.$reg->bib_number.' ditetapkan '.strtoupper($data['status']).'.']);
    }

    // ------------------------------------------------------ lokasi admin ----

    /**
     * POST /admin/location — admin memilih pos sebelum scan timing (timing_point_id), atau
     * melepas pos (null, mis. pindah ke meja racepack). Balikan = payload sesi untuk pos itu.
     */
    public function location(Request $request, GtrTimingSession $session): JsonResponse
    {
        $data = $request->validate(['timing_point_id' => ['nullable', 'integer', 'exists:gtr_timing_points,id']]);
        $user = $request->user();

        if (! empty($data['timing_point_id'])) {
            $eventId = Event::gtrId();
            if (! $eventId) {
                return response()->json(['message' => 'Event GTR belum ada.'], 422);
            }
            app(MarshalService::class)->assign($user, $eventId, (int) $data['timing_point_id']);
            $user->activeAssignment()?->forceFill(['device_id' => SessionController::deviceId($request), 'device_seen_at' => now()])->save();
        } else {
            $user->timingPointAssignments()->where('is_active', true)->update(['is_active' => false]);
        }

        return response()->json(['device_id' => SessionController::deviceId($request)] + $session->payload($user));
    }

    // ------------------------------------------------------- finish board ----

    /** GET /admin/finish — per kategori: finisher terbaru & podium (top 3) per gender. */
    public function finish(): JsonResponse
    {
        $cats = GtrCategory::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get(['id', 'name']);
        $row = fn (GtrResult $r) => [
            'bib' => Bib::normalize($r->registration?->bib_number) ?? (string) $r->registration?->bib_number,
            'name' => $r->registration?->full_name,
            'gender' => $r->registration?->genderCode(),
            'finish_at_ms' => DateTimeMs::toMs($r->finish_time),
            'gun_time_ms' => $r->gun_time_ms,
            'net_time_ms' => $r->net_time_ms,
            'rank_overall' => $r->rank_overall,
            'rank_gender' => $r->rank_gender,
            'needs_review' => $r->needs_review,
        ];

        return response()->json([
            'categories' => $cats->map(function (GtrCategory $c) use ($row) {
                $base = GtrResult::with('registration:id,bib_number,full_name,gender')
                    ->where('gtr_category_id', $c->id)->where('status', GtrResult::STATUS_FINISHER);
                $podium = (clone $base)->whereBetween('rank_gender', [1, 3])->orderBy('rank_gender')->get()
                    ->groupBy(fn (GtrResult $r) => $r->registration?->genderCode() ?? '-');

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'finishers' => (clone $base)->count(),
                    'eligible' => GtrRegistration::query()->raceEligible()->where('gtr_category_id', $c->id)->count(),
                    'podium' => [
                        'M' => ($podium['M'] ?? collect())->map($row)->values(),
                        'F' => ($podium['F'] ?? collect())->map($row)->values(),
                    ],
                    'latest' => (clone $base)->whereNotNull('finish_time')->orderByDesc('finish_time')->limit(10)->get()->map($row)->values(),
                ];
            })->values(),
            'results_published' => (bool) GtrSetting::first()?->results_published,
            'server_time_ms' => DateTimeMs::toMs(now()),
        ]);
    }

    // ---------------------------------------------------- scan bermasalah ----

    /** GET /admin/issues — scan tak terdaftar / salah kategori, scan janggal (jam), hasil perlu review. */
    public function issues(): JsonResponse
    {
        $with = ['timingPoint:id,code,name', 'scanner:id,name', 'registration:id,bib_number,full_name'];

        $unmatched = GtrScanLog::with($with)->where('is_ignored', true)
            ->whereIn('ignore_reason', self::ACTIONABLE_REASONS)
            ->latest('scanned_at')->limit(100)->get();
        $flagged = GtrScanLog::with($with)->where('is_flagged', true)->where('is_ignored', false)
            ->latest('scanned_at')->limit(100)->get();
        $ignoredByAdmin = GtrScanLog::with($with)->where('is_ignored', true)->where('ignore_reason', GtrScanLog::REASON_ADMIN)
            ->latest('ignored_at')->limit(50)->get();
        $review = GtrResult::with(['registration:id,bib_number,full_name,gtr_category_id', 'category:id,name'])
            ->where('needs_review', true)->orderBy('gtr_category_id')->get();

        return response()->json([
            'unmatched' => $unmatched->map(fn ($s) => $this->scanRow($s))->values(),
            'flagged' => $flagged->map(fn ($s) => $this->scanRow($s))->values(),
            'ignored' => $ignoredByAdmin->map(fn ($s) => $this->scanRow($s))->values(),
            'review' => $review->map(fn (GtrResult $r) => [
                'bib' => Bib::normalize($r->registration?->bib_number) ?? (string) $r->registration?->bib_number,
                'name' => $r->registration?->full_name,
                'category' => $r->category?->name,
                'status' => $r->status,
                'note' => $r->review_note,
            ])->values(),
        ]);
    }

    /** POST /admin/scans/{scan}/{action} — ignore | restore | relink (body: bib). */
    public function scanAction(Request $request, GtrScanLog $scan, string $action): JsonResponse
    {
        try {
            match ($action) {
                'ignore' => $this->admin->ignoreScan($scan, $request->user()),
                'restore' => $this->admin->restoreScan($scan),
                'relink' => $this->admin->relinkScan($scan, (string) $request->validate(['bib' => ['required']])['bib']),
            };
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => match ($action) {
                'ignore' => 'Scan diabaikan.',
                'restore' => 'Scan dipulihkan.',
                'relink' => 'Scan dihubungkan ke BIB '.$scan->fresh()->registration?->bib_number.'.',
            },
            'scan' => $this->scanRow($scan->fresh(['timingPoint:id,code,name', 'scanner:id,name', 'registration:id,bib_number,full_name'])),
        ]);
    }

    // ----------------------------------------------------- scan per pos ----

    /**
     * GET /admin/points/{point}/scans?q=&filter=all|valid|ignored — scan di satu pos (terbaru dulu,
     * maks 300) + peserta yang seharusnya lewat pos ini tapi belum punya scan sah.
     */
    public function pointScans(Request $request, GtrTimingPoint $point): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $catIds = $point->categories()->pluck('gtr_categories.id');

        $base = GtrScanLog::where('gtr_timing_point_id', $point->id);
        $validRegIds = (clone $base)->valid()->distinct()->pluck('gtr_registration_id');

        $scans = (clone $base)->with(['timingPoint:id,code,name', 'scanner:id,name', 'registration:id,bib_number,full_name'])
            ->when($filter === 'valid', fn ($w) => $w->where('is_ignored', false)->whereNotNull('gtr_registration_id'))
            ->when($filter === 'ignored', fn ($w) => $w->where('is_ignored', true))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('bib_input', 'like', '%'.$q.'%')
                ->orWhereHas('registration', fn ($r) => $r->where('full_name', 'like', '%'.$q.'%')->orWhere('bib_number', 'like', '%'.$q.'%'))))
            ->orderByDesc('scanned_at')->orderByDesc('id')->limit(300)->get();

        $missing = GtrRegistration::query()->raceEligible()->with('category:id,name')
            ->whereIn('gtr_category_id', $catIds)
            ->whereNotIn('id', $validRegIds)
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('full_name', 'like', '%'.$q.'%')->orWhere('bib_number', 'like', '%'.$q.'%')))
            ->orderBy('bib_number')
            ->get(['id', 'gtr_category_id', 'bib_number', 'full_name', 'race_status']);

        return response()->json([
            'point' => ['id' => $point->id, 'code' => $point->code, 'name' => $point->name, 'type' => $point->type],
            'stats' => [
                'valid' => (clone $base)->valid()->count(),
                'participants' => $validRegIds->count(),
                'ignored' => (clone $base)->where('is_ignored', true)->count(),
                'expected' => GtrRegistration::query()->raceEligible()->whereIn('gtr_category_id', $catIds)->count(),
                'missing' => $missing->count(),
            ],
            'scans' => $scans->map(fn (GtrScanLog $s) => $this->scanRow($s))->values(),
            'missing' => $missing->map(fn (GtrRegistration $r) => [
                'bib' => Bib::normalize($r->bib_number) ?? (string) $r->bib_number,
                'name' => $r->full_name,
                'category' => $r->category?->name,
                'race_status' => $r->race_status,
            ])->values(),
            'server_time_ms' => DateTimeMs::toMs(now()),
        ]);
    }

    /** POST /admin/points/{point}/scans — Body: bib, time "HH:MM(:SS)" WITA di hari race. */
    public function addScan(Request $request, GtrTimingPoint $point): JsonResponse
    {
        $data = $request->validate([
            'bib' => ['required', 'string', 'max:32'],
            'time' => ['required', 'string', 'regex:/^\d{1,2}:\d{2}(:\d{2}(\.\d{1,3})?)?$/'],
        ]);

        $at = $this->raceDayTime($data['time']);
        if ($at->greaterThan(now()->addMinute())) {
            return response()->json(['message' => 'Jam scan tidak boleh di masa depan.'], 422);
        }

        try {
            $scan = $this->admin->addManualScan($point, trim($data['bib']), $at, $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        $scan->load(['timingPoint:id,code,name', 'scanner:id,name', 'registration:id,bib_number,full_name']);

        return response()->json([
            'message' => 'Scan BIB '.($scan->registration?->bib_number ?? $data['bib']).' di '.$point->code.' '.$at->format('H:i:s').' ditambahkan'
                .($scan->is_ignored ? ' (tercatat duplikat < 60 detik)' : '').'.',
            'scan' => $this->scanRow($scan),
        ]);
    }

    // ---------------------------------------------------------- reset uji ----

    /** GET /admin/reset — jumlah data uji yang akan terhapus + apakah reset dari aplikasi aktif. */
    public function resetPreview(Request $request, GtrRaceDayReset $reset): JsonResponse
    {
        return response()->json([
            'enabled' => filled(config('services.gtr.reset_code')),
            'counts' => $reset->counts(true, true, $request->user()->currentAccessToken()?->id),
            'last_reset_ms' => GtrSetting::timingResetAtMs(),
        ]);
    }

    /** POST /admin/reset — Body: code (GTR_RESET_CODE), racepack?, logout?. HP admin ini tetap login. */
    public function reset(Request $request, GtrRaceDayReset $reset): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'racepack' => ['nullable', 'boolean'],
            'logout' => ['nullable', 'boolean'],
        ]);

        $expected = (string) config('services.gtr.reset_code');
        if ($expected === '') {
            return response()->json(['message' => 'Reset dari aplikasi belum diaktifkan (isi GTR_RESET_CODE di server).'], 403);
        }
        if (! hash_equals($expected, $data['code'])) {
            Log::warning('GTR reset data uji: kode salah', ['user_id' => $request->user()->id]);

            return response()->json(['message' => 'Kode reset salah.'], 422);
        }

        $racepack = (bool) ($data['racepack'] ?? false);
        $logout = (bool) ($data['logout'] ?? false);
        $keep = $request->user()->currentAccessToken()?->id;
        $counts = $reset->counts($racepack, $logout, $keep);
        $done = $reset->run($racepack, $logout, $keep);

        Log::warning('GTR reset data uji dari scanner', ['user_id' => $request->user()->id, 'counts' => $counts, 'backup' => $done['backup']]);

        return response()->json([
            'message' => 'Data uji dihapus.',
            'counts' => $counts,
            'reset_at_ms' => DateTimeMs::toMs($done['reset_at']),
        ]);
    }

    // ------------------------------------------------------------ helper ----

    /** Jam "HH:MM(:SS(.mmm))" WITA di tanggal race (tanggal gun time, jadwal start, atau hari ini). */
    private function raceDayTime(string $time): Carbon
    {
        $tz = config('app.timezone');
        $cats = GtrCategory::where('is_active', true)->get();
        $ref = $cats->pluck('gun_fired_at')->filter()->first() ?? $cats->pluck('gun_start')->filter()->first() ?? now();

        return Carbon::parse(Carbon::parse($ref)->setTimezone($tz)->format('Y-m-d').' '.$time, $tz);
    }

    private function issueCount(): int
    {
        return GtrScanLog::where('is_ignored', true)->whereIn('ignore_reason', self::ACTIONABLE_REASONS)->count()
            + GtrScanLog::where('is_flagged', true)->where('is_ignored', false)->count()
            + GtrResult::where('needs_review', true)->count();
    }

    private function scanRow(GtrScanLog $s): array
    {
        return [
            'id' => $s->id,
            'point' => $s->timingPoint?->code,
            'point_name' => $s->timingPoint?->name,
            'bib_input' => $s->bib_input,
            'bib' => $s->registration ? (Bib::normalize($s->registration->bib_number) ?? (string) $s->registration->bib_number) : null,
            'name' => $s->registration?->full_name,
            'scanned_at_ms' => DateTimeMs::toMs($s->scanned_at),
            'source' => $s->source,
            'scanner' => $s->scanner?->name,
            'is_ignored' => $s->is_ignored,
            'reason' => $s->ignore_reason,
            'reason_label' => $s->ignore_reason ? (GtrScanLog::REASONS[$s->ignore_reason] ?? $s->ignore_reason) : null,
            'is_flagged' => $s->is_flagged,
        ];
    }

    /** Scan sah terakhir per peserta. @return Collection<int, GtrScanLog> */
    private function lastSeen(Collection $regIds): Collection
    {
        return GtrScanLog::query()->valid()->with('timingPoint:id,code,type')
            ->whereIn('gtr_registration_id', $regIds)
            ->orderBy('scanned_at')
            ->get(['id', 'gtr_registration_id', 'gtr_timing_point_id', 'scanned_at'])
            ->keyBy('gtr_registration_id'); // keyBy menimpa → yang terakhir (paling baru) tersisa
    }

    /**
     * Status lapangan (seperti LiveTracker): DNF/DQ hanya dari race_status resmi — status di
     * gtr_results selama lomba masih sementara (belum finish = "dnf" sampai pelari tiba).
     */
    private function liveStatus(GtrRegistration $r, ?GtrScanLog $last, ?string $manual = null, ?string $fallback = null): string
    {
        return match (true) {
            in_array($manual, [GtrResult::STATUS_DNF, GtrResult::STATUS_DQ, GtrResult::STATUS_DNS], true) => $manual,
            $manual === GtrResult::STATUS_FINISHER => 'finished',
            $fallback !== null => $fallback,
            in_array($r->race_status, [GtrRegistration::RACE_DNF, GtrRegistration::RACE_DQ], true) => $r->race_status,
            $r->result?->finish_time !== null => 'finished',
            $last !== null => 'on_course',
            default => 'not_started',
        };
    }
}
