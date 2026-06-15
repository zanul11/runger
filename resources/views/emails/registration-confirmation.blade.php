@php
  $reg = $registration;
  $cat = $reg->category;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Konfirmasi Pendaftaran</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f9;font-family:Arial,Helvetica,sans-serif;color:#1a1a1a">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f4f9;padding:24px 0">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 30px rgba(15,38,128,.1)">

          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(125deg,#2A4FC8,#0F2680);padding:28px 32px;color:#ffffff">
              <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;opacity:.85;margin-bottom:6px">Gerung Trail Run 2026</div>
              <div style="font-size:22px;font-weight:800">Pendaftaran Berhasil 🎉</div>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:28px 32px">
              <p style="margin:0 0 14px;font-size:15px;line-height:1.6">
                Halo <strong>{{ $reg->full_name }}</strong>,
              </p>
              <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#444">
                Terima kasih sudah mendaftar di <strong>Gerung Trail Run 2026</strong>. Pendaftaranmu sudah kami terima. Berikut detailnya:
              </p>

              <!-- No. Registrasi -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7ff;border:1px solid #dbe4ff;border-radius:10px;margin-bottom:20px">
                <tr>
                  <td style="padding:16px 20px;text-align:center">
                    <div style="font-size:11px;letter-spacing:1.5px;text-transform:uppercase;color:#2A4FC8;font-weight:700;margin-bottom:4px">No. Registrasi</div>
                    <div style="font-size:24px;font-weight:800;color:#0F2680;letter-spacing:1px">{{ $reg->nomor_registrasi }}</div>
                  </td>
                </tr>
              </table>

              <!-- Detail rows -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px">
                <tr>
                  <td style="padding:9px 0;color:#777;border-bottom:1px solid #eef1f6">Kategori</td>
                  <td style="padding:9px 0;text-align:right;font-weight:700;border-bottom:1px solid #eef1f6">{{ $cat?->name }} ({{ $cat?->distance }})</td>
                </tr>
                <tr>
                  <td style="padding:9px 0;color:#777;border-bottom:1px solid #eef1f6">Nama</td>
                  <td style="padding:9px 0;text-align:right;font-weight:700;border-bottom:1px solid #eef1f6">{{ $reg->full_name }}</td>
                </tr>
                <tr>
                  <td style="padding:9px 0;color:#777;border-bottom:1px solid #eef1f6">Ukuran Jersey</td>
                  <td style="padding:9px 0;text-align:right;font-weight:700;border-bottom:1px solid #eef1f6">{{ $reg->size }}</td>
                </tr>
                <tr>
                  <td style="padding:9px 0;color:#777;border-bottom:1px solid #eef1f6">WhatsApp</td>
                  <td style="padding:9px 0;text-align:right;font-weight:700;border-bottom:1px solid #eef1f6">{{ $reg->whatsapp }}</td>
                </tr>
                <tr>
                  <td style="padding:9px 0;color:#777">Status Pembayaran</td>
                  <td style="padding:9px 0;text-align:right">
                    <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:#fff4e0;color:#b76e00;font-weight:700;font-size:12px;text-transform:uppercase">Belum Bayar</span>
                  </td>
                </tr>
              </table>

              <!-- CTA -->
              <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px">
                <tr>
                  <td align="center">
                    <a href="{{ route('gtr.account.transaction') }}" style="display:inline-block;background:#2A4FC8;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;padding:13px 28px;border-radius:999px">Lanjutkan Pembayaran →</a>
                  </td>
                </tr>
              </table>

              <p style="margin:24px 0 0;font-size:13px;line-height:1.6;color:#888">
                Selesaikan pembayaran agar slotmu terkonfirmasi. Jika kamu tidak merasa melakukan pendaftaran ini, abaikan email ini.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background:#0F2680;padding:20px 32px;text-align:center;color:#c7d2ff;font-size:12px">
              <div style="font-weight:700;color:#ffffff;margin-bottom:4px">Runners Gerung</div>
              <div>Gerung, Lombok Barat, NTB · Gerung Trail Run 2026</div>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
