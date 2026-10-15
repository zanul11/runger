@php
  $reg = $registration;
  $cat = $reg->category;
  $setting = \App\Models\GtrSetting::first();
  $appUrl = route('gtr.login');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ambil Race Pack</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <tr>
            <td style="background:#101010;padding:28px 32px;color:#ffffff;border-bottom:4px solid #E53935">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.75;margin-bottom:6px">{{ $setting->title ?? 'Gerung Trail Run 2026' }} · H-7</div>
              <div style="font-size:22px;font-weight:800">Saatnya Ambil Race Pack 🎁</div>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">Halo <strong>{{ $reg->full_name }}</strong>,</p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">
                Race day tinggal seminggu lagi! Jangan lupa ambil race pack-mu (BIB, jersey, dan perlengkapan lainnya) sesuai jadwal di bawah.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 20px">
                <tr><td style="padding:10px 16px;color:#667;width:40%">Kategori</td><td style="padding:10px 16px;font-weight:700">{{ $cat->distance ?? '' }} · {{ $cat->name ?? '-' }}</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Nomor BIB</td><td style="padding:10px 16px;font-weight:800;border-top:1px solid #eef">{{ $reg->bib_number ?: '-' }}</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Ukuran Jersey</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $reg->size }}</td></tr>
                @if($setting?->racepack_schedule)
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Jadwal</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $setting->racepack_schedule }}</td></tr>
                @endif
                @if($setting?->racepack_location)
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Lokasi</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">{{ $setting->racepack_location }}</td></tr>
                @endif
              </table>

              <p style="margin:0 0 18px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;line-height:1.6;color:#334155">
                Tunjukkan <strong>QR e-ticket</strong> di Runger App (My Race → detail race) ke petugas.
                @if($setting?->racepack_note)<br>{{ $setting->racepack_note }}@endif
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ $appUrl }}" style="display:inline-block;background:#E53935;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 34px;border-radius:10px">Buka E-Ticket di Runger App →</a>
                  </td>
                </tr>
              </table>

              <p style="margin:22px 0 0;font-size:13px;line-height:1.7;color:#666">
                Sambil menunggu, cek course map & atur target finish di Runger App untuk membuat race plan dan pace band-mu.<br><br>
                Salam, Panitia {{ $setting->title ?? 'Gerung Trail Run 2026' }}.
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#101010;padding:18px 32px;color:#9a9a9a;font-size:11px;line-height:1.6">
              Email ini dikirim otomatis. Abaikan jika race pack sudah kamu ambil.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
