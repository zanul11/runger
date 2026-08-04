@php
  $reg = $registration;
  $cat = $reg->category;
  $idr = fn ($n) => 'IDR ' . number_format((int) $n, 0, ',', '.');
  $base = $reg->baseAmount();
  $disc = (int) $reg->discount_amount;
  $gross = $base + $disc;
  $fee = $reg->serviceFee();
  $total = $base + $fee;
  $setting = \App\Models\GtrSetting::first();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Pembayaran</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(125deg,#16a34a,#15803d);padding:28px 32px;color:#ffffff">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.85;margin-bottom:6px">{{ $setting->title ?? 'Gerung Trail Run 2026' }}</div>
              <div style="font-size:22px;font-weight:800">Pembayaran Berhasil ✅</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">
                Halo <strong>{{ $reg->full_name }}</strong>,
              </p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">
                Pembayaran pendaftaranmu sudah kami terima. Berikut e-ticket & rincian pembayaranmu.
                Simpan email ini dan tunjukkan BIB saat pengambilan race pack.
              </p>

              <!-- BIB / e-ticket -->
              @if($reg->bib_number)
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px">
                <tr>
                  <td align="center" style="background:#0F2680;border-radius:12px;padding:18px;color:#fff">
                    <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;opacity:.8">Nomor BIB</div>
                    <div style="font-size:34px;font-weight:900;letter-spacing:2px;margin-top:2px">{{ $reg->bib_number }}</div>
                  </td>
                </tr>
              </table>
              @endif

              <!-- Detail peserta -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 18px">
                <tr><td style="padding:11px 16px;background:#f8fafc;color:#667;width:42%">No. Registrasi</td><td style="padding:11px 16px;font-weight:700">{{ $reg->nomor_registrasi }}</td></tr>
                <tr><td style="padding:11px 16px;background:#f8fafc;color:#667;border-top:1px solid #eef">Kategori</td><td style="padding:11px 16px;border-top:1px solid #eef">{{ $cat->distance ?? '' }} · {{ $cat->name ?? '-' }}</td></tr>
                <tr><td style="padding:11px 16px;background:#f8fafc;color:#667;border-top:1px solid #eef">Metode</td><td style="padding:11px 16px;border-top:1px solid #eef">{{ $reg->pay }}</td></tr>
                <tr><td style="padding:11px 16px;background:#f8fafc;color:#667;border-top:1px solid #eef">Tanggal Bayar</td><td style="padding:11px 16px;border-top:1px solid #eef">{{ optional($reg->paid_at)->timezone('Asia/Makassar')->translatedFormat('d F Y · H:i') ?? '-' }} WITA</td></tr>
              </table>

              <!-- Rincian biaya -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px">
                <tr><td style="padding:10px 16px;color:#667">Biaya Pendaftaran</td><td align="right" style="padding:10px 16px">{{ $idr($gross) }}</td></tr>
                @if($disc > 0)
                <tr><td style="padding:10px 16px;color:#16a34a;border-top:1px solid #eef">Voucher {{ $reg->discount_code }}</td><td align="right" style="padding:10px 16px;color:#16a34a;border-top:1px solid #eef">− {{ $idr($disc) }}</td></tr>
                @endif
                @if($fee > 0)
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Biaya Layanan</td><td align="right" style="padding:10px 16px;border-top:1px solid #eef">{{ $idr($fee) }}</td></tr>
                @endif
                <tr><td style="padding:12px 16px;font-weight:800;border-top:2px solid #0F2680;background:#f8fafc">Total Dibayar</td><td align="right" style="padding:12px 16px;font-weight:800;border-top:2px solid #0F2680;background:#f8fafc">{{ $idr($total) }}</td></tr>
              </table>

              <p style="margin:22px 0 0;font-size:13px;line-height:1.7;color:#666">
                Sampai jumpa di garis start! 🏃‍♂️<br>
                Salam, Panitia {{ $setting->title ?? 'Gerung Trail Run 2026' }}.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#0b1533;padding:18px 32px;color:#aab;font-size:11px;line-height:1.6">
              Email ini dikirim otomatis. Untuk bantuan, hubungi panitia melalui kanal resmi.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
