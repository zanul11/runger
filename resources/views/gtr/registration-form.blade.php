<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Formulir Pendaftaran — {{ $setting->title ?? 'Gerung Trail Run 2026' }}</title>
<style>
  :root{ --ink:#111; --line:#111; --muted:#555; --accent:#E53935; }
  *{box-sizing:border-box}
  html,body{margin:0;padding:0;background:#f3f3f3;color:var(--ink);font-family:'Segoe UI',Arial,sans-serif}
  .sheet{
    width:210mm;min-height:297mm;margin:12px auto;background:#fff;padding:14mm 14mm 12mm;
    box-shadow:0 2px 14px rgba(0,0,0,.15)
  }
  .toolbar{max-width:210mm;margin:12px auto 0;text-align:right}
  .btn{background:var(--accent);color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:700;cursor:pointer}
  .hd{display:flex;align-items:center;gap:14px;border-bottom:3px solid var(--ink);padding-bottom:10px}
  .hd img{height:52px;width:auto}
  .hd .t{flex:1}
  .hd h1{margin:0;font-size:19px;letter-spacing:.02em}
  .hd .sub{font-size:11px;color:var(--muted);margin-top:3px;line-height:1.5}
  .hd .rightbox{border:1.5px solid var(--ink);padding:6px 10px;font-size:10px;min-width:46mm}
  .hd .rightbox b{display:block;font-size:9px;letter-spacing:.08em;text-transform:uppercase;color:var(--muted)}
  .hd .rightbox .ln{height:15px;border-bottom:1px dotted #999;margin-top:4px}

  .sect{margin-top:14px}
  .sect > .lbl{font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;background:var(--ink);color:#fff;display:inline-block;padding:3px 9px}
  .grid{display:flex;flex-wrap:wrap;gap:9px 16px;margin-top:9px}
  .f{flex:1 1 100%}
  .f.half{flex:1 1 calc(50% - 16px)}
  .f.third{flex:1 1 calc(33.33% - 16px)}
  .f .cap{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.04em}
  .f .fill{height:22px;border-bottom:1.4px solid var(--ink);margin-top:2px}
  .opts{display:flex;flex-wrap:wrap;gap:6px 14px;margin-top:5px;font-size:12px}
  .opt{display:inline-flex;align-items:center;gap:6px}
  .box{width:13px;height:13px;border:1.5px solid var(--ink);display:inline-block}
  .nik{display:flex;gap:3px;margin-top:3px}
  .nik .cell{width:9mm;height:11mm;border:1.4px solid var(--ink);border-radius:2px}
  .cat-row{display:flex;align-items:center;gap:9px;border:1.2px solid #bbb;padding:7px 10px;margin-top:7px;font-size:12px}
  .cat-row .box{width:15px;height:15px}
  .cat-row .nm{font-weight:700}
  .cat-row .pr{margin-left:auto;font-size:11px;color:var(--muted)}
  .decl{font-size:10.5px;color:#333;line-height:1.6;margin-top:8px;border:1.2px solid #bbb;padding:9px 11px}
  .sign{display:flex;justify-content:space-between;margin-top:16px;gap:24px}
  .sign .b{flex:1;text-align:center;font-size:11px;color:var(--muted)}
  .sign .b .space{height:64px}
  .sign .b .ln{border-top:1.2px solid var(--ink);padding-top:4px}
  .foot{margin-top:12px;font-size:9px;color:#888;text-align:center;border-top:1px solid #ddd;padding-top:8px}

  @media print{
    html,body{background:#fff}
    .toolbar{display:none}
    .sheet{margin:0;box-shadow:none;width:auto;min-height:auto;padding:10mm}
    @page{size:A4;margin:8mm}
  }
</style>
</head>
<body>
  <div class="toolbar">
    <button class="btn" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
  </div>

  <div class="sheet">
    <div class="hd">
      <img src="{{ asset('assets/gtr/logo.jpeg') }}" alt="GTR">
      <div class="t">
        <h1>Formulir Pendaftaran</h1>
        <div class="sub">
          {{ $setting->title ?? 'Gerung Trail Run 2026' }}<br>
          {{ $setting->date_text ?? 'Minggu, 29 November 2026' }} · {{ $setting->location_text ?? 'Bukit Keteri, Gerung — Lombok Barat' }}
        </div>
      </div>
      <div class="rightbox">
        <b>No. Registrasi</b><div class="ln"></div>
        <b style="margin-top:7px">Tanggal Daftar</b><div class="ln"></div>
      </div>
    </div>

    {{-- Kategori --}}
    <div class="sect">
      <span class="lbl">Pilih Kategori</span>
      @forelse($categories as $cat)
        <div class="cat-row">
          <span class="box"></span>
          <span class="nm">{{ $cat->distance }} — {{ $cat->name }}</span>
          <span class="pr">
            @if($cat->price_normal) IDR {{ number_format($cat->price_normal, 0, ',', '.') }} @endif
          </span>
        </div>
      @empty
        <div class="cat-row"><span class="box"></span> <span>_____________________________</span></div>
      @endforelse
    </div>

    {{-- Data peserta --}}
    <div class="sect">
      <span class="lbl">Data Peserta</span>
      <div class="grid">
        <div class="f"><div class="cap">Nama Lengkap (sesuai KTP)</div><div class="fill"></div></div>

        <div class="f">
          <div class="cap">NIK (16 digit)</div>
          <div class="nik">
            @for($i=0;$i<16;$i++)<div class="cell"></div>@endfor
          </div>
        </div>

        <div class="f half">
          <div class="cap">Jenis Kelamin</div>
          <div class="opts">
            @foreach(\App\Models\GtrRegistration::GENDERS as $g)
              <span class="opt"><span class="box"></span> {{ $g }}</span>
            @endforeach
          </div>
        </div>
        <div class="f half"><div class="cap">Tanggal Lahir</div><div class="fill"></div></div>

        <div class="f half">
          <div class="cap">Golongan Darah</div>
          <div class="opts">
            @foreach(\App\Models\GtrRegistration::BLOOD_TYPES as $b)
              <span class="opt"><span class="box"></span> {{ $b }}</span>
            @endforeach
          </div>
        </div>
        <div class="f half">
          <div class="cap">Ukuran Jersey</div>
          <div class="opts">
            @foreach(\App\Models\GtrRegistration::SIZES as $s)
              <span class="opt"><span class="box"></span> {{ $s }}</span>
            @endforeach
          </div>
        </div>

        <div class="f half"><div class="cap">Email</div><div class="fill"></div></div>
        <div class="f half"><div class="cap">No. WhatsApp</div><div class="fill"></div></div>

        <div class="f"><div class="cap">Alamat</div><div class="fill"></div></div>
        <div class="f"><div class="cap">Klub / Komunitas (opsional)</div><div class="fill"></div></div>
      </div>
    </div>

    {{-- Kontak darurat --}}
    <div class="sect">
      <span class="lbl">Kontak Darurat</span>
      <div class="grid">
        <div class="f half"><div class="cap">Nama Kontak Darurat</div><div class="fill"></div></div>
        <div class="f half"><div class="cap">No. HP Darurat (beda dari WhatsApp)</div><div class="fill"></div></div>
      </div>
    </div>

    {{-- Pembayaran --}}
    <div class="sect">
      <span class="lbl">Pembayaran</span>
      <div class="opts" style="margin-top:8px">
        <span class="opt"><span class="box"></span> Transfer Bank</span>
        <span class="opt"><span class="box"></span> Tunai (Cash)</span>
        <span class="opt"><span class="box"></span> QRIS</span>
        <span class="opt">Nominal: <span style="display:inline-block;width:44mm;border-bottom:1.4px solid #111">&nbsp;</span></span>
      </div>
    </div>

    {{-- Pernyataan --}}
    <div class="sect">
      <span class="lbl">Pernyataan</span>
      <div class="decl">
        Dengan ini saya menyatakan bahwa data yang saya isi benar, saya dalam kondisi sehat dan mampu
        mengikuti lomba, serta menyetujui seluruh syarat &amp; ketentuan penyelenggaraan
        {{ $setting->title ?? 'Gerung Trail Run 2026' }}. <b>(☐ Setuju &amp; menandatangani di bawah)</b>
      </div>
      <div class="sign">
        <div class="b"><div class="space"></div><div class="ln">Peserta<br>(Nama &amp; Tanda Tangan)</div></div>
        <div class="b"><div class="space"></div><div class="ln">Panitia / Petugas<br>(Nama &amp; Tanda Tangan)</div></div>
      </div>
    </div>

    <div class="foot">
      Formulir pendaftaran offline · {{ $setting->title ?? 'Gerung Trail Run 2026' }} · dicetak {{ now()->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA
    </div>
  </div>

  <script>
    // Auto-buka dialog cetak bila diakses dengan ?print=1
    if (new URLSearchParams(location.search).get('print') === '1') {
      window.addEventListener('load', () => setTimeout(() => window.print(), 400))
    }
  </script>
</body>
</html>
