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
  $awards = collect(preg_split('/\r?\n/', (string) ($cat->award_prize ?? '')))
    ->map(fn ($l) => trim(ltrim(trim($l), '-•*· ')))->filter()->values();
  // fitur peserta, dikelompokkan per tahap
  $groups = [
    ['Sebelum race', '#E53935', [
      ['🎽', 'Race pack', 'Jersey resmi GTR 2026 (ukuran ' . ($reg->size ?: 'pilihanmu') . ') & nomor BIB.'],
      ['🎟️', 'E-ticket QR', 'Terkirim begitu lunas — tinggal tunjukkan saat ambil race pack.'],
      ['⏱️', 'Race plan & pace band', 'Atur target finish, lihat perkiraan jam tiba di tiap pos & pace per KM, lalu unduh pace band untuk dibawa lari.'],
      ['🌦️', 'Info cuaca & briefing', 'Prakiraan cuaca race day, pengingat ambil race pack (H-7) & briefing lengkap (H-1) langsung ke email.'],
    ]],
    ['Saat race day', '#F97316', [
      ['🗺️', 'Course map interaktif', 'Rute, profil elevasi, posisi WS & Check Point, info jalur licin/curam — bisa disimpan offline & tunjukkan posisimu dengan GPS.'],
    ]],
    ['Setelah finish', '#16A34A', [
      ['🏅', 'Hasil resmi & e-sertifikat', 'Waktu finish, peringkat, split per pos, e-sertifikat, plus gambar hasil larimu (nama, waktu & peta rute) yang siap diposting ke Instagram Story.'],
      ['📸', 'Foto race', 'Foto kamu di jalur dikumpulkan per BIB di Runger App — tinggal unduh.'],
    ]],
  ];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lunasi & Buka Semua Fitur Peserta</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <!-- Header: ajakan membuka fitur -->
          <tr>
            <td style="background:#101010;padding:30px 32px 26px;color:#ffffff;border-bottom:4px solid #E53935">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#FF8A80;margin-bottom:8px">{{ $setting->title ?? 'Gerung Trail Run 2026' }} · {{ $cat->distance ?? '' }}</div>
              <div style="font-size:24px;font-weight:800;line-height:1.25">Satu langkah lagi, semua ini jadi milikmu 🏃</div>
              <div style="font-size:14px;line-height:1.6;color:#d4d4d4;margin-top:10px">
                Halo <strong style="color:#ffffff">{{ $reg->full_name }}</strong>, pendaftaranmu (No. {{ $reg->nomor_registrasi }}) tinggal dilunasi untuk membuka semua fitur peserta di bawah ini.
              </div>
            </td>
          </tr>

          <!-- Fitur (isi utama) -->
          <tr>
            <td style="padding:26px 32px 6px">
              @foreach($groups as [$label, $color, $items])
                <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;font-weight:800;color:{{ $color }};margin:{{ $loop->first ? '0' : '22px' }} 0 10px">{{ $label }}</div>
                @foreach($items as [$icon, $title, $desc])
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 10px;background:#f8fafc;border:1px solid #e6e9f2;border-radius:12px">
                    <tr>
                      <td valign="top" width="60" style="padding:14px 0 14px 14px">
                        <div style="width:44px;height:44px;line-height:44px;text-align:center;border-radius:12px;background:#ffffff;border:1px solid #e6e9f2;font-size:22px">{{ $icon }}</div>
                      </td>
                      <td valign="top" style="padding:14px 16px 14px 10px">
                        <div style="font-size:15px;font-weight:800;color:#1a1a1a">{{ $title }}</div>
                        <div style="font-size:13.5px;line-height:1.55;color:#4b5563;margin-top:3px">{{ $desc }}</div>
                      </td>
                    </tr>
                  </table>
                @endforeach
              @endforeach

              @if($awards->count())
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:14px 0 0;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px">
                <tr>
                  <td style="padding:13px 16px;font-size:13.5px;line-height:1.6;color:#166534">
                    <strong>🏆 Award &amp; prize {{ $cat->distance ?? '' }}:</strong> {{ $awards->implode(' · ') }}
                  </td>
                </tr>
              </table>
              @endif
              <div style="font-size:12.5px;color:#6b7280;margin:12px 0 0;text-align:center">Semua fitur bisa diakses di <strong>Runger App</strong> dengan akun ini.</div>
            </td>
          </tr>

          <!-- Ringkasan pembayaran + CTA -->
          <tr>
            <td style="padding:20px 32px 28px">
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:2px solid #101010;border-radius:14px;overflow:hidden">
                <tr>
                  <td style="padding:16px 18px">
                    <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;font-weight:800;color:#6b7280">Total yang perlu dibayar</div>
                    <div style="font-size:28px;font-weight:900;color:#101010;margin-top:4px">{{ $idr($total) }}</div>
                    <div style="font-size:12.5px;color:#6b7280;margin-top:6px;line-height:1.6">
                      Biaya pendaftaran ({{ $cat?->currentPriceLabel() ?? 'Harga' }}) {{ $idr($gross) }}@if($disc > 0) · voucher {{ $reg->discount_code }} − {{ $idr($disc) }}@endif @if($fee > 0) · biaya layanan {{ $idr($fee) }}@endif
                    </div>
                    @if($cat && $cat->earlyBirdEnded())
                      <div style="font-size:12.5px;color:#991b1b;margin-top:8px">Periode early bird sudah berakhir — berlaku harga normal.</div>
                    @elseif($cat && $cat->earlyBirdActive() && $cat->early_bird_until)
                      <div style="font-size:12.5px;color:#92400e;margin-top:8px">Harga early bird berlaku sampai <strong>{{ $cat->early_bird_until->translatedFormat('d F Y') }}</strong>.</div>
                    @endif
                  </td>
                </tr>
                <tr>
                  <td align="center" style="padding:0 18px 18px">
                    <a href="{{ $appUrl }}" style="display:block;background:#E53935;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;padding:15px 20px;border-radius:10px">
                      Lunasi &amp; Buka Semua Fitur →
                    </a>
                    <div style="margin-top:10px;font-size:12px;color:#889">Atau buka: <a href="{{ $appUrl }}" style="color:#E53935">{{ $appUrl }}</a></div>
                  </td>
                </tr>
              </table>

              <p style="margin:20px 0 0;font-size:13px;line-height:1.7;color:#666">
                Slot &amp; BIB-mu aman setelah lunas, dan e-ticket langsung dikirim ke email ini. Sampai jumpa di garis start!<br>
                Salam, Panitia {{ $setting->title ?? 'Gerung Trail Run 2026' }}.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#101010;padding:18px 32px;color:#9a9a9a;font-size:11px;line-height:1.6">
              Email ini dikirim otomatis. Abaikan jika kamu sudah membayar.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
