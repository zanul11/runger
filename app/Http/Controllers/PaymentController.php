<?php

namespace App\Http\Controllers;

use App\Models\GtrRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class PaymentController extends Controller
{
    protected function configureMidtrans(): void
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production');
        Config::$isSanitized = (bool) config('midtrans.is_sanitized');
        Config::$is3ds = (bool) config('midtrans.is_3ds');
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
        $amount = (int) ($registration->amount ?: ($registration->category->price_early_bird ?? 0));

        if ($amount <= 0) {
            return back()->with('success', 'Nominal pembayaran belum tersedia. Hubungi panitia.');
        }

        $orderId = $registration->nomor_registrasi . '-' . substr((string) time(), -6);

        $this->configureMidtrans();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => [[
                'id' => 'GTR-' . $registration->gtr_category_id,
                'price' => $amount,
                'quantity' => 1,
                'name' => 'GTR ' . ($registration->category->distance ?? '') . ' ' . ($registration->category->name ?? ''),
            ]],
            'customer_details' => [
                'first_name' => $registration->full_name,
                'email' => $registration->email,
                'phone' => $registration->whatsapp,
            ],
            'callbacks' => [
                'finish' => route('gtr.payment.finish'),
            ],
        ];

        try {
            $snap = Snap::createTransaction($params);
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap create error: ' . $e->getMessage());

            return back()->with('success', 'Gagal membuat transaksi pembayaran. Silakan coba lagi.');
        }

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

        $registration = $orderId
            ? GtrRegistration::where('midtrans_order_id', $orderId)->first()
            : null;

        if ($registration && $registration->runner_id === $runner->id) {
            $this->configureMidtrans();

            try {
                $status = \Midtrans\Transaction::status($orderId);
                $trx = is_array($status) ? ($status['transaction_status'] ?? null) : ($status->transaction_status ?? null);
                $fraud = is_array($status) ? ($status['fraud_status'] ?? null) : ($status->fraud_status ?? null);

                $new = $registration->payment_status;
                if ($trx === 'capture') {
                    $new = ($fraud === 'challenge') ? 'pending' : 'paid';
                } elseif ($trx === 'settlement') {
                    $new = 'paid';
                } elseif (in_array($trx, ['cancel', 'deny', 'expire'], true)) {
                    $new = 'cancelled';
                } elseif ($trx === 'pending') {
                    $new = 'pending';
                }

                $registration->update([
                    'payment_status' => $new,
                    'paid_at' => $new === 'paid' ? ($registration->paid_at ?? now()) : $registration->paid_at,
                ]);
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

        $registration = GtrRegistration::where('midtrans_order_id', $orderId)->first();
        if (! $registration) {
            return response()->json(['message' => 'order not found'], 404);
        }

        $new = $registration->payment_status;

        if ($status === 'capture') {
            $new = ($fraud === 'challenge') ? 'pending' : 'paid';
        } elseif ($status === 'settlement') {
            $new = 'paid';
        } elseif (in_array($status, ['cancel', 'deny', 'expire'], true)) {
            $new = 'cancelled';
        } elseif ($status === 'pending') {
            $new = 'pending';
        }

        $registration->update([
            'payment_status' => $new,
            'paid_at' => $new === 'paid' ? now() : $registration->paid_at,
        ]);

        return response()->json(['message' => 'ok']);
    }
}
