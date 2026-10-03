@php
  $reg = $registration;
  $cat = $reg->category;
  $setting = \App\Models\GtrSetting::first();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perubahan Ukuran Jersey</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <!-- Header -->
          <tr>
            <td style="background:#0F2680;padding:28px 32px;color:#ffffff">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.85;margin-bottom:6px">{{ $setting->title ?? 'Gerung Trail Run 2026' }}</div>
              <div style="font-size:22px;font-weight:800">Ukuran Jersey Diperbarui 👕</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">
                Halo <strong>{{ $reg->full_name }}</strong>,
              </p>
              <p style="margin:0 0 20px;font-size:14px;line-height:1.7;color:#444">
                Sesuai permintaanmu, ukuran jersey untuk pendaftaran <strong>{{ $reg->nomor_registrasi }}</strong> sudah kami perbarui.
              </p>

              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e6e9f2;border-radius:12px;overflow:hidden;font-size:14px;margin:0 0 22px">
                <tr><td style="padding:10px 16px;color:#667">Kategori</td><td align="right" style="padding:10px 16px">{{ trim(($cat->distance ?? '') . ' · ' . ($cat->name ?? '-'), ' ·') }}</td></tr>
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">No. BIB</td><td align="right" style="padding:10px 16px;border-top:1px solid #eef">{{ $reg->bib_number ?: '-' }}</td></tr>
                @if($oldSize)
                <tr><td style="padding:10px 16px;color:#667;border-top:1px solid #eef">Ukuran lama</td><td align="right" style="padding:10px 16px;border-top:1px solid #eef;text-decoration:line-through;color:#889">{{ $oldSize }}</td></tr>
                @endif
                <tr><td style="padding:12px 16px;font-weight:800;border-top:2px solid #0F2680;background:#f5f7ff">Ukuran baru</td><td align="right" style="padding:12px 16px;font-weight:800;border-top:2px solid #0F2680;background:#f5f7ff">{{ $reg->size }}</td></tr>
              </table>

              <p style="margin:0;font-size:13px;line-height:1.7;color:#666">
                Jersey ukuran baru akan diberikan saat pengambilan race pack. Jika ada yang tidak sesuai, silakan hubungi panitia.<br><br>
                Salam, Panitia {{ $setting->title ?? 'Gerung Trail Run 2026' }}.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#0b1533;padding:18px 32px;color:#aab;font-size:11px;line-height:1.6">
              Email ini dikirim otomatis oleh panitia.
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
