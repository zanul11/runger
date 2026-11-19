@php
  $reg = $registration;
  $registerUrl = route('gtr.road');
  $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode('Bukit Keteri, Banyu Urip, Gerung, Lombok Barat');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Besok! Road to GTR</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <tr>
            <td style="background:#101010;padding:28px 32px;color:#ffffff;border-bottom:4px solid #2E7D32">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.75;margin-bottom:6px">Road to Gerung Trail Run 2026 · Besok!</div>
              <div style="font-size:22px;font-weight:800">Fun Trail &amp; Penanaman Pohon 🌱</div>
            </td>
          </tr>

          <tr>
            <td style="padding:0;background:#101010">
              <a href="{{ $registerUrl }}"><img src="{{ asset('assets/gtr/road-to-gtr/poster.jpg') }}" width="600" alt="Poster Road to GTR — Penanaman Pohon &amp; Fun Trail Bukit Keteri, Minggu 15 November 2026, 06:00 WITA" style="display:block;width:100%;max-width:600px;height:auto;border:0"></a>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">Halo <strong>{{ $reg->full_name }}</strong>,</p>
              <p style="margin:0 0 14px;font-size:14px;line-height:1.7;color:#444">
                @if($registered)
                  Mengingatkan, <strong>besok</strong> kita ketemu di <strong>Fun Trail &amp; Penanaman Pohon Road to GTR 2026</strong> di Bukit Keteri, bukit yang jadi jalur lomba Gerung Trail Run 2026. Kamu sudah terdaftar ✅
                @else
                  <strong>Besok</strong> ada <strong>Fun Trail &amp; Penanaman Pohon Road to GTR 2026</strong> di Bukit Keteri, bukit yang jadi jalur lomba Gerung Trail Run 2026. Yuk ikut!
                @endif
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 20px">
                <tr><td style="padding:10px 16px;color:#667;width:40%">Hari / Tanggal</td><td style="padding:10px 16px;font-weight:700">Minggu, 15 November 2026</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Waktu</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">06:00 WITA s.d. selesai<br><span style="font-weight:400;color:#666;font-size:12px">mohon datang tepat waktu</span></td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Lokasi</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">Bukit Keteri — Desa Banyu Urip, Gerung<br><a href="{{ $mapsUrl }}" style="font-weight:400;font-size:12px;color:#2E7D32">Buka di Google Maps ↗</a></td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Acara</td><td style="padding:10px 16px;font-weight:700;border-top:1px solid #eef">Fun Trail Road to GTR &amp; Penanaman Pohon</td></tr>
              </table>

              <p style="margin:0 0 8px;font-size:14px;font-weight:700">Persiapan:</p>
              <ul style="margin:0 0 20px;padding-left:20px;font-size:14px;line-height:1.8;color:#444">
                <li>Pakaian olahraga &amp; sepatu lari/trail</li>
                <li>Botol minum pribadi (kurangi sampah plastik)</li>
                <li>Topi, sunscreen, handuk kecil</li>
                <li>Sarapan ringan sebelum berangkat</li>
              </ul>

              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">
                Kita lari santai bareng sambil kenalan sama jalur GTR, lalu menanam pohon bersama warga supaya Bukit Keteri tetap hijau.
              </p>

              @if($remaining > 0)
              <p style="margin:0 0 16px;font-size:14px;line-height:1.7;color:#444">
                @if($registered)
                  Ajak teman &amp; keluarga! Masih ada <strong>{{ $remaining }} slot</strong> lagi (kuota {{ \App\Models\RoadToGtrRegistration::QUOTA }}), daftar gratis lewat link ini:
                @else
                  <strong>Belum daftar?</strong> Masih ada <strong>{{ $remaining }} slot</strong> lagi (kuota {{ \App\Models\RoadToGtrRegistration::QUOTA }}). Daftar sekarang, gratis — ajak juga teman &amp; keluarga!
                @endif
              </p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ $registerUrl }}" style="display:inline-block;background:#2E7D32;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 34px;border-radius:10px">Daftar Road to GTR →</a>
                    <div style="margin-top:10px;font-size:13px;color:#555"><a href="{{ $registerUrl }}" style="color:#2E7D32">{{ $registerUrl }}</a></div>
                  </td>
                </tr>
              </table>
              @endif

              <p style="margin:20px 0 0;font-size:13px;line-height:1.7;color:#666">
                Info lebih lanjut: <strong>Hairul Fahmi</strong> — <a href="https://wa.me/6285936607558" style="color:#2E7D32">+62 859-3660-7558</a> (WhatsApp).
              </p>

              <p style="margin:22px 0 0;font-size:13px;line-height:1.7;color:#666">
                Sampai jumpa besok pagi! 🏃🌳<br>Salam lestari, salam olahraga,<br>Panitia Gerung Trail Run 2026 — Runners Gerung.
              </p>
            </td>
          </tr>

          <tr>
            <td style="background:#101010;padding:18px 32px;color:#9a9a9a;font-size:11px;line-height:1.6">
              Email ini dikirim ke peserta terdaftar Gerung Trail Run 2026.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
