@php
  $reg = $registration;
  $cat = $reg->category;
  $idr = fn ($n) => 'IDR ' . number_format((int) $n, 0, ',', '.');
  $base = $reg->baseAmount();      // harga berlaku (early bird/normal) − voucher
  $disc = (int) $reg->discount_amount;
  $gross = $base + $disc;
  $fee = $reg->serviceFee();
  $total = $base + $fee;
  $setting = \App\Models\GtrSetting::first();
  $appUrl = route('gtr.login');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengingat Pembayaran</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(125deg,#d97706,#b45309);padding:28px 32px;color:#ffffff">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.85;margin-bottom:6px">{{ $setting->title ?? 'Gerung Trail Run 2026' }}</div>
              <div style="font-size:22px;font-weight:800">Pembayaran Belum Selesai ⏳</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">
                Halo <strong>{{ $reg->full_name }}</strong>,
              </p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">
                Pendaftaranmu di <strong>{{ $cat->distance ?? '' }} · {{ $cat->name ?? '-' }}</strong>
                (No. {{ $reg->nomor_registrasi }}) <strong>belum lunas</strong>.
                Segera selesaikan pembayaran agar slot &amp; BIB-mu aman. Setelah lunas,
                e-ticket (BIB + QR) langsung dikirim ke email ini.
              </p>

              @if($cat && $cat->earlyBirdEnded())
              <p style="margin:0 0 18px;padding:11px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;font-size:13px;color:#991b1b">
                Periode <strong>early bird sudah berakhir</strong> — harga yang berlaku sekarang adalah harga normal.
              </p>
              @elseif($cat && $cat->earlyBirdActive() && $cat->early_bird_until)
              <p style="margin:0 0 18px;padding:11px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;font-size:13px;color:#92400e">
                Harga <strong>early bird</strong> berlaku sampai <strong>{{ $cat->early_bird_until->translatedFormat('d F Y') }}</strong>. Jangan sampai kelewatan!
              </p>
              @endif

              <!-- Rincian yang harus dibayar -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 22px">
                <tr><td style="padding:10px 16px;color:#667">Biaya Pendaftaran ({{ $cat?->currentPriceLabel() ?? 'Harga' }})</td><td align="right" style="padding:10px 16px">{{ $idr($gross) }}</td></tr>
                @if($disc > 0)
                <tr><td style="padding:10px 16px;color:#16a34a;border-top:1px solid #eef">Voucher {{ $reg->discount_code }}</td><td align="right" style="padding:10px 16px;color:#16a34a;border-top:1px solid #eef">− {{ $idr($disc) }}</td></tr>
                @endif
                @if($fee > 0)
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Biaya Layanan</td><td align="right" style="padding:10px 16px;border-top:1px solid #eef">{{ $idr($fee) }}</td></tr>
                @endif
                <tr><td style="padding:12px 16px;font-weight:800;border-top:2px solid #d97706;background:#fffdf5">Total Harus Dibayar</td><td align="right" style="padding:12px 16px;font-weight:800;border-top:2px solid #d97706;background:#fffdf5">{{ $idr($total) }}</td></tr>
              </table>

              <!-- CTA -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ $appUrl }}" style="display:inline-block;background:#0F2680;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 34px;border-radius:10px">
                      Masuk &amp; Selesaikan Pembayaran →
                    </a>
                    <div style="margin-top:10px;font-size:12px;color:#889">Atau buka: <a href="{{ $appUrl }}" style="color:#0F2680">{{ $appUrl }}</a></div>
                  </td>
                </tr>
              </table>

              <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#666">
                Sampai jumpa di garis start! 🏃<br>
                Salam, Panitia {{ $setting->title ?? 'Gerung Trail Run 2026' }}.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#0b1533;padding:18px 32px;color:#aab;font-size:11px;line-height:1.6">
              Email ini dikirim otomatis. Abaikan jika kamu sudah membayar.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
