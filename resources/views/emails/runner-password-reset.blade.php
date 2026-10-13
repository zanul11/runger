@php
  $setting = \App\Models\GtrSetting::first();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password</title>
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
              <div style="font-size:22px;font-weight:800">Reset Password Akun 🔑</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">
                Halo <strong>{{ $runner->full_name }}</strong>,
              </p>
              <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#444">
                Kami menerima permintaan untuk membuat password baru akun peserta GTR dengan email <strong>{{ $runner->email }}</strong>. Klik tombol di bawah untuk membuat password baru.
              </p>

              <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 22px">
                <tr>
                  <td align="center" style="background:#E53935;border-radius:10px">
                    <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;color:#ffffff;font-size:14px;font-weight:800;letter-spacing:1px;text-transform:uppercase;text-decoration:none">Buat Password Baru</a>
                  </td>
                </tr>
              </table>

              <p style="margin:0 0 14px;font-size:13px;line-height:1.7;color:#666">
                Link ini berlaku selama {{ $expire }} menit. Jika tombol tidak bisa diklik, salin link berikut ke browser:<br>
                <a href="{{ $url }}" style="color:#1B3FAE;word-break:break-all">{{ $url }}</a>
              </p>
              <p style="margin:0;font-size:13px;line-height:1.7;color:#666">
                Jika kamu tidak meminta reset password, abaikan email ini. Password kamu tidak akan berubah.<br><br>
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
