@php
  $reg = $registration;
  $cat = $reg->category;
  $setting = \App\Models\GtrSetting::first();
  $appUrl = route('gtr.login');
  $title = $setting->title ?? 'Gerung Trail Run 2026';
  // Persyaratan RPC dari pengaturan GTR: baris bernomor → daftar, baris lain → catatan.
  $reqLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $setting?->rpc_requirements))));
  $reqItems = array_values(array_map(fn ($l) => preg_replace('/^\d+[.)]\s*/', '', $l), array_filter($reqLines, fn ($l) => preg_match('/^\d+[.)]/', $l))));
  $reqNotes = array_values(array_filter($reqLines, fn ($l) => ! preg_match('/^\d+[.)]/', $l)));
  $docs = \App\Models\GtrDocument::published()->get();
  $row = 'padding:10px 16px;border-top:1px solid #eef';
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
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.75;margin-bottom:6px">{{ $title }} · Race Pack Collection</div>
              <div style="font-size:22px;font-weight:800">Saatnya Ambil Race Pack 🎁</div>
            </td>
          </tr>

          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">Halo <strong>{{ $reg->full_name }}</strong>,</p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">
                Race day sudah dekat! Ambil race pack-mu (BIB, jersey, dan perlengkapan lainnya) sesuai jadwal di bawah. <strong>Simpan e-ticket di email ini</strong> dan <strong>siapkan dokumen wajib</strong> supaya proses pengambilan cepat.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 20px">
                <tr><td style="padding:10px 16px;color:#667;width:40%">Kategori</td><td style="padding:10px 16px;font-weight:700">{{ $cat->distance ?? '' }} · {{ $cat->name ?? '-' }}</td></tr>
                <tr><td style="{{ $row }};color:#667">Nomor BIB</td><td style="{{ $row }};font-weight:800">{{ $reg->bib_number ?: '-' }}</td></tr>
                <tr><td style="{{ $row }};color:#667">Ukuran Jersey</td><td style="{{ $row }};font-weight:700">{{ $reg->size }}</td></tr>
                @if($setting?->racepack_schedule)
                <tr><td style="{{ $row }};color:#667">Jadwal</td><td style="{{ $row }};font-weight:700">{{ $setting->racepack_schedule }}</td></tr>
                @endif
                @if($setting?->racepack_location)
                <tr><td style="{{ $row }};color:#667">Lokasi</td><td style="{{ $row }}"><strong>{{ $setting->racepack_location }}</strong>@if($setting->racepack_address)<br><span style="font-size:12px;color:#667">{{ $setting->racepack_address }}</span>@endif
                  @if($setting->racepack_maps_url)<br><a href="{{ $setting->racepack_maps_url }}" style="font-size:12px;color:#E53935;font-weight:700;text-decoration:none">Buka di Google Maps →</a>@endif</td></tr>
                @endif
              </table>

              <!-- QR E-Ticket (sama seperti di aplikasi peserta) -->
              <div style="margin:0 0 10px;font-size:15px;font-weight:800">🎟️ E-Ticket kamu</div>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 20px">
                <tr>
                  <td align="center" style="background:#ffffff;border:2px dashed #E53935;border-radius:12px;padding:20px">
                    <img src="{{ $message->embedData($reg->qrPng(), 'eticket-' . $reg->nomor_registrasi . '.png', 'image/png') }}" alt="QR {{ $reg->nomor_registrasi }}" width="210" height="210" style="display:block;width:210px;height:210px">
                    <div style="margin-top:10px;font-size:16px;font-weight:800;letter-spacing:1px">{{ $reg->nomor_registrasi }}</div>
                    <div style="margin-top:4px;font-size:12px;color:#667;line-height:1.5">Tunjukkan QR ini ke petugas saat pengambilan race pack.<br>QR yang sama juga ada di Runger App (My Race → detail race).</div>
                  </td>
                </tr>
              </table>

              @if($reqItems)
              <div style="margin:0 0 10px;font-size:15px;font-weight:800">📋 Wajib dibawa saat pengambilan</div>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.5;margin:0 0 {{ $reqNotes ? '8' : '18' }}px">
                @foreach($reqItems as $i => $item)
                <tr>
                  <td width="30" valign="top" style="padding:5px 0"><span style="display:inline-block;width:22px;height:22px;line-height:22px;border-radius:11px;background:#E53935;color:#fff;font-size:12px;font-weight:700;text-align:center">{{ $i + 1 }}</span></td>
                  <td valign="top" style="padding:6px 0;color:#333">{{ $item }}</td>
                </tr>
                @endforeach
              </table>
              @foreach($reqNotes as $note)
              <p style="margin:0 0 18px;font-size:12px;line-height:1.6;color:#667">{{ $note }}</p>
              @endforeach
              @endif

              @if($docs->isNotEmpty())
              <div style="margin:0 0 6px;font-size:15px;font-weight:800">⬇️ Unduh & print dokumennya</div>
              <p style="margin:0 0 10px;font-size:13px;line-height:1.6;color:#555">Print, isi, dan tanda tangani sebelum datang ke lokasi.</p>
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 20px">
                @foreach($docs as $doc)
                <tr>
                  <td style="padding:12px 16px;{{ $loop->first ? '' : 'border-top:1px solid #eef;' }}font-weight:700;color:#1a1a1a">{{ $doc->title_id }}</td>
                  <td align="right" style="padding:12px 16px;{{ $loop->first ? '' : 'border-top:1px solid #eef;' }}white-space:nowrap">
                    <a href="{{ route('gtr.doc', [$doc->slug, 'id']) }}" style="display:inline-block;background:#101010;color:#ffffff;text-decoration:none;font-size:12px;font-weight:700;padding:7px 12px;border-radius:8px">PDF</a>
                    @if($doc->has_en && $doc->pdf_en)
                    <a href="{{ route('gtr.doc', [$doc->slug, 'en']) }}" style="display:inline-block;margin-left:4px;border:1px solid #cbd2e0;color:#334155;text-decoration:none;font-size:12px;font-weight:700;padding:6px 10px;border-radius:8px">EN</a>
                    @endif
                  </td>
                </tr>
                @endforeach
              </table>
              @endif

              @if($setting?->rpc_proxy_note)
              <p style="margin:0 0 18px;padding:12px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;font-size:13px;line-height:1.6;color:#7c2d12">
                <strong>Tidak bisa datang sendiri?</strong><br>{{ $setting->rpc_proxy_note }}
              </p>
              @endif

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center">
                    <a href="{{ $appUrl }}" style="display:inline-block;background:#E53935;color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 34px;border-radius:10px">Buka E-Ticket di Runger App →</a>
                  </td>
                </tr>
              </table>

              <p style="margin:22px 0 0;font-size:13px;line-height:1.7;color:#666">
                @if($setting?->rpc_contact)Ada pertanyaan? Hubungi <strong>{{ $setting->rpc_contact }}</strong>.<br><br>@endif
                Salam, Panitia {{ $title }}.
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
