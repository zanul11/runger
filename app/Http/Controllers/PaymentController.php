<?php

namespace App\Http\Controllers;

use App\Models\GtrPayment;
use App\Models\GtrRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class PaymentController extends Controller
{
    /** Berapa lama satu Snap berlaku (menit). */
    protected const SNAP_EXPIRY_MINUTES = 1440; // 24 jam

    protected function configureMidtrans(): void
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
        Config::$isSanitized = (bool) config('midtrans.is_sanitized');
        Config::$is3ds = (bool) config('midtrans.is_3ds');
    }

    /**
     * Cari payment via order_id. Untuk transaksi lama (dibuat sebelum tabel payments ada),
     * backfill satu baris dari data di registrasi agar webhook-nya tetap tertangani.
     */
    protected function resolvePayment(?string $orderId): ?GtrPayment
    {
        if (! $orderId) {
            return null;
        }

        $payment = GtrPayment::where('order_id', $orderId)->first();
        if ($payment) {
            return $payment;
        }

        $registration = GtrRegistration::where('midtrans_order_id', $orderId)->first();
        if (! $registration) {
            return null;
        }

        return $registration->payments()->create([
            'order_id' => $orderId,
            'amount' => (int) $registration->amount,
            'status' => $registration->payment_status === 'paid' ? 'paid' : 'pending',
            'snap_token' => $registration->snap_token,
            'paid_at' => $registration->paid_at,
        ]);
    }

    /** Petakan status transaksi Midtrans → status payment kami. */
    protected function mapStatus(?string $trx, ?string $fraud): string
    {
        return match (true) {
            $trx === 'capture' => $fraud === 'challenge' ? 'pending' : 'paid',
            $trx === 'settlement' => 'paid',
            $trx === 'pending' => 'pending',
            in_array($trx, ['cancel', 'deny'], true) => 'cancelled',
            $trx === 'expire' => 'expired',
            $trx === 'failure' => 'failed',
            default => 'pending',
        };
    }

    /**
     * Turunkan status registrasi dari kumpulan payment-nya.
     * paid jika ada yang paid; pending jika ada attempt aktif; selain itu cancelled (bisa bayar lagi).
     */
    protected function syncRegistrationStatus(GtrRegistration $registration): void
    {
        $statuses = $registration->payments()->pluck('status');

        if ($statuses->contains('paid')) {
            $new = 'paid';
        } elseif ($statuses->contains('pending')) {
            $new = 'pending';
        } elseif ($statuses->isNotEmpty()) {
            $new = 'cancelled';
        } else {
            $new = $registration->payment_status;
        }

        $paidAt = $new === 'paid'
            ? ($registration->paid_at ?? optional($registration->payments()->where('status', 'paid')->first())->paid_at ?? now())
            : $registration->paid_at;

        $update = ['payment_status' => $new, 'paid_at' => $paidAt];

        // Kunci biaya pendaftaran yang BENAR-BENAR dibayar (gross - admin fee),
        // supaya tampilan tetap sesuai walau early bird sudah lewat setelahnya.
        if ($new === 'paid' && ! $registration->amount) {
            $paid = $registration->payments()->where('status', 'paid')->latest('id')->first();
            if ($paid) {
                $update['amount'] = max(0, (int) $paid->amount - GtrRegistration::ADMIN_FEE);
            }
        }

        $registration->update($update);
    }

    /**
     * Create a Snap transaction and redirect the participant to the Midtrans payment page.
     */
    public function pay(GtrRegistration $registration)
    {
        $runner = Auth::guard('runner')->user();
        abort_unless($registration->runner_id === $runner->id, 403);

        if ($registration->payment_status === 'paid') {
            return redirect()->route('gtr.account.transaction')->with('success', 'Pendaftaran ini sudah dibayar.');
        }

        $registration->loadMissing('category');
        // Harga berbasis tanggal: early bird bila masih aktif, selain itu normal.
        $amount = $registration->baseAmount();

        if ($amount <= 0) {
            return back()->with('success', 'Nominal pembayaran belum tersedia. Hubungi panitia.');
        }

        // Total yang ditagih = biaya pendaftaran + biaya layanan (0 bila non-QRIS).
        $fee = $registration->serviceFee();
        $gross = $amount + $fee;

        // Pakai ulang Snap yang masih aktif (pending & belum kedaluwarsa) — hindari order_id bengkak.
        $active = $registration->payments()
            ->where('status', 'pending')
            ->where('amount', $gross)
            ->whereNotNull('snap_redirect_url')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('id')
            ->first();

        if ($active) {
            return redirect()->away($active->snap_redirect_url);
        }

        $orderId = $registration->nomor_registrasi . '-' . substr((string) time(), -6);
        $expiresAt = now()->addMinutes(self::SNAP_EXPIRY_MINUTES);

        $this->configureMidtrans();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $gross,
            ],
            'item_details' => [
                [
                    'id' => 'GTR-' . $registration->gtr_category_id,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => 'GTR ' . ($registration->category->distance ?? '') . ' ' . ($registration->category->name ?? ''),
                ],
                [
                    'id' => 'ADMIN-FEE',
                    'price' => $fee,
                    'quantity' => 1,
                    'name' => 'Biaya Layanan',
                ],
            ],
            'customer_details' => [
                'first_name' => $registration->full_name,
                'email' => $registration->email,
                'phone' => $registration->whatsapp,
            ],
            'callbacks' => [
                'finish' => route('gtr.payment.finish'),
            ],
            'expiry' => [
                'unit' => 'minutes',
                'duration' => self::SNAP_EXPIRY_MINUTES,
            ],
            'enabled_payments' => ['other_qris'],
        ];

        try {
            $snap = Snap::createTransaction($params);
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap create error: ' . $e->getMessage());

            return back()->with('success', 'Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

        // Catat attempt pembayaran (riwayat per order_id). amount = total ditagih (termasuk biaya admin).
        $registration->payments()->create([
            'order_id' => $orderId,
            'amount' => $gross,
            'status' => 'pending',
            'snap_token' => $snap->token ?? null,
            'snap_redirect_url' => $snap->redirect_url ?? null,
            'expires_at' => $expiresAt,
        ]);

        // Simpan attempt terbaru ke registrasi (amount = biaya pendaftaran/base, untuk rincian tampilan).
        $registration->update([
            'midtrans_order_id' => $orderId,
            'snap_token' => $snap->token ?? null,
            'amount' => $amount,
            'payment_status' => 'pending',
        ]);

        return redirect()->away($snap->redirect_url);
    }

    /**
     * Payment status page — shown after Midtrans redirects back (callbacks.finish).
     * Refreshes the latest status from Midtrans so it's accurate even before the webhook lands.
     */
    public function finish(Request $request)
    {
        $runner = Auth::guard('runner')->user();
        $orderId = $request->query('order_id');

        $payment = $this->resolvePayment($orderId);
        $registration = $payment?->registration;

        if ($payment && $registration && $registration->runner_id === $runner->id) {
            $this->configureMidtrans();

            try {
                $status = \Midtrans\Transaction::status($orderId);
                $trx = is_array($status) ? ($status['transaction_status'] ?? null) : ($status->transaction_status ?? null);
                $fraud = is_array($status) ? ($status['fraud_status'] ?? null) : ($status->fraud_status ?? null);

                $mapped = $this->mapStatus($trx, $fraud);
                $payment->update([
                    'status' => $mapped,
                    'paid_at' => $mapped === 'paid' ? ($payment->paid_at ?? now()) : $payment->paid_at,
                ]);

                $this->syncRegistrationStatus($registration);
            } catch (\Throwable $e) {
                Log::warning('Midtrans status refresh failed: ' . $e->getMessage());
            }
        }

        return view('pages.runner.payment-finish', [
            'tab' => 'transaction',
            'runner' => $runner,
            'reg' => ($registration && $registration->runner_id === $runner->id) ? $registration->fresh('category') : null,
        ]);
    }

    /**
     * Midtrans HTTP(S) notification / callback listener (webhook).
     * Configure URL in Midtrans dashboard: {APP_URL}/api/midtrans/notification
     */
    public function notification(Request $request)
    {
        $this->configureMidtrans();

        try {
            $notif = new Notification();
        } catch (\Throwable $e) {
            Log::warning('Midtrans notification parse error: ' . $e->getMessage());

            return response()->json(['message' => 'invalid notification'], 400);
        }

        $orderId = $notif->order_id ?? null;
        $status = $notif->transaction_status ?? null;
        $fraud = $notif->fraud_status ?? null;

        $payment = $this->resolvePayment($orderId);

        // Order_id tak dikenal → ack 200 supaya Midtrans berhenti retry (jangan 404).
        if (! $payment) {
            Log::info('Midtrans notif untuk order_id tak dikenal, diabaikan: ' . $orderId);

            return response()->json(['message' => 'order not found, ignored'], 200);
        }

        $mapped = $this->mapStatus($status, $fraud);

        // Jangan turunkan payment yang sudah paid (anti webhook telat/ganda).
        if ($payment->status !== 'paid') {
            $payment->update([
                'status' => $mapped,
                'paid_at' => $mapped === 'paid' ? ($payment->paid_at ?? now()) : $payment->paid_at,
                'raw' => $request->all(),
            ]);
        }

        $this->syncRegistrationStatus($payment->registration);

        return response()->json(['message' => 'ok']);
    }
}
