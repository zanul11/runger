<?php

namespace App\Http\Controllers\Api\Timing;

use App\Casts\DateTimeMs;
use App\Http\Controllers\Controller;
use App\Models\GtrExpense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pengeluaran panitia (GTR Crew → Pengeluaran): role bendahara & admin.
 * Tiap pengeluaran: tanggal, nominal, PIC, keterangan + foto / PDF kwitansi (disk privat).
 */
class ExpenseController extends Controller
{
    /** GET /expenses */
    public function index(): JsonResponse
    {
        $items = GtrExpense::with(['creator:id,name', 'editor:id,name'])
            ->orderByDesc('spent_on')->orderByDesc('id')->get();

        return response()->json([
            'items' => $items->map(fn (GtrExpense $e) => $this->row($e))->values(),
            'total' => (int) $items->sum('amount'),
            'count' => $items->count(),
            'pics' => $items->pluck('pic')->unique()->values(),
        ]);
    }

    /** POST /expenses — multipart: spent_on, amount, pic, description, receipt (file, opsional). */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $expense = new GtrExpense($data + ['created_by' => $request->user()->id]);
        $this->attachReceipt($request, $expense);
        $expense->save();

        return response()->json(['item' => $this->row($expense->fresh(['creator:id,name', 'editor:id,name']))], 201);
    }

    /** POST /expenses/{expense} — sama dengan store; remove_receipt=1 untuk menghapus kwitansi. */
    public function update(Request $request, GtrExpense $expense): JsonResponse
    {
        $expense->fill($this->validated($request) + ['updated_by' => $request->user()->id]);
        if ($request->boolean('remove_receipt') && ! $request->hasFile('receipt')) {
            $expense->deleteReceipt();
            $expense->forceFill(['receipt_path' => null, 'receipt_mime' => null]);
        }
        $this->attachReceipt($request, $expense);
        $expense->save();

        return response()->json(['item' => $this->row($expense->fresh(['creator:id,name', 'editor:id,name']))]);
    }

    /** DELETE /expenses/{expense} — kwitansi ikut terhapus (GtrExpense::booted). */
    public function destroy(GtrExpense $expense): JsonResponse
    {
        $expense->delete();

        return response()->json(['message' => 'Pengeluaran dihapus.']);
    }

    /** GET /expenses/{expense}/receipt — file kwitansi (butuh token; disk privat). */
    public function receipt(GtrExpense $expense): StreamedResponse
    {
        abort_unless($expense->hasReceipt(), 404, 'Kwitansi tidak ada.');

        return Storage::disk(GtrExpense::DISK)->response($expense->receipt_path, $this->receiptName($expense), [
            'Content-Type' => $expense->receipt_mime ?: 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** GET /expenses/export — CSV (pemisah ";" agar langsung rapi di Excel Indonesia). */
    public function export(): StreamedResponse
    {
        $items = GtrExpense::orderBy('spent_on')->orderBy('id')->get();

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No', 'Tanggal', 'Keterangan', 'PIC', 'Nominal', 'Kwitansi'], ';');
            foreach ($items as $i => $e) {
                fputcsv($out, [$i + 1, $e->spent_on->format('d/m/Y'), $e->description, $e->pic, $e->amount, $e->receipt_path ? 'Ada' : '-'], ';');
            }
            fputcsv($out, ['', '', 'TOTAL', '', $items->sum('amount'), ''], ';');
            fclose($out);
        }, 'pengeluaran-gtr-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'spent_on' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'pic' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:2000'],
            'receipt' => ['nullable', 'file', 'max:10240', 'mimetypes:image/jpeg,image/png,image/webp,image/heic,image/heif,application/pdf'],
        ], [
            'receipt.max' => 'Ukuran kwitansi maksimal 10 MB.',
            'receipt.mimetypes' => 'Kwitansi harus berupa foto (JPG/PNG/WEBP) atau PDF.',
        ]);
        unset($data['receipt']);
        $data['pic'] = trim($data['pic']);
        $data['description'] = trim($data['description']);

        return $data;
    }

    private function attachReceipt(Request $request, GtrExpense $expense): void
    {
        if (! $request->hasFile('receipt')) {
            return;
        }
        $file = $request->file('receipt');
        $path = $file->storeAs(GtrExpense::DIR, Str::ulid().'.'.($file->guessExtension() ?: 'bin'), GtrExpense::DISK);
        $expense->deleteReceipt();
        $expense->forceFill(['receipt_path' => $path, 'receipt_mime' => $file->getMimeType()]);
    }

    private function receiptName(GtrExpense $e): string
    {
        return 'kwitansi-'.$e->id.'-'.$e->spent_on->format('Ymd').'.'.pathinfo($e->receipt_path, PATHINFO_EXTENSION);
    }

    private function row(GtrExpense $e): array
    {
        return [
            'id' => $e->id,
            'spent_on' => $e->spent_on->toDateString(),
            'amount' => $e->amount,
            'pic' => $e->pic,
            'description' => $e->description,
            'has_receipt' => $e->receipt_path !== null,
            'receipt_pdf' => $e->receipt_mime === 'application/pdf',
            'created_by' => $e->creator?->name,
            'updated_by' => $e->editor?->name,
            'created_at_ms' => DateTimeMs::toMs($e->created_at),
            'updated_at_ms' => DateTimeMs::toMs($e->updated_at),
        ];
    }
}
