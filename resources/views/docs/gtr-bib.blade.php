{{--
  BIB GTR (A5 landscape 210 × 148 mm) untuk dompdf — satu BIB per halaman, posisi absolut dalam mm.
  Atas (putih): identitas, SOS panitia, kategori, gender, sponsor, info darurat.
  Bawah: panel profil elevasi (PNG dari App\Support\GtrBibPdf::panel, terbalik 180°).
--}}
@php
  use App\Support\GtrBibPdf as B;
  $font = fn ($f) => resource_path('fonts/' . $f);
  $panelTop = B::H - B::PANEL_H;
  // Sponsor: tinggi logo menyesuaikan jumlah agar muat 1–2 baris dalam kotak sponsor.
  $spCount = count($sponsors);
  $spH = $spCount <= 6 ? 10 : ($spCount <= 12 ? 8 : 7.2);
  $perRow = $spCount > 7 ? (int) ceil($spCount / 2) : max(1, $spCount);
  $spMaxW = min(24, 100 / $perRow - 2.8);
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>BIB {{ $event }}</title>
<style>
  @font-face{font-family:'ArchivoBlack';src:url('{{ $font('Archivo-900.ttf') }}') format('truetype')}
  @font-face{font-family:'ArchivoXB';src:url('{{ $font('Archivo-800.ttf') }}') format('truetype')}
  @font-face{font-family:'Jakarta';src:url('{{ $font('PlusJakartaSans-400.ttf') }}') format('truetype');font-weight:normal}
  @font-face{font-family:'Jakarta';src:url('{{ $font('PlusJakartaSans-700.ttf') }}') format('truetype');font-weight:bold}
  @font-face{font-family:'JakartaSB';src:url('{{ $font('PlusJakartaSans-600.ttf') }}') format('truetype')}

  @page{margin:0}
  body{margin:0;padding:0;font-family:'Jakarta';color:#141414}
  .bib{position:relative;width:210mm;height:148mm;overflow:hidden;page-break-after:always;background:#FFFFFF}
  .bib.last{page-break-after:auto}
  .abs{position:absolute}

  /* Kolom kiri */
  .logo{left:8mm;top:6mm;height:19mm}
  .sos{left:8mm;top:28mm;width:36mm;height:12.4mm;border:.45mm solid #E53935;border-radius:2mm;background:#FFFFFF}
  .sos .t{position:absolute;left:0;top:0;width:36mm;height:5mm;background:#E53935;color:#FFFFFF;text-align:center;font-family:'ArchivoBlack';font-size:7pt;line-height:4mm;letter-spacing:.08em}
  .sos .p{position:absolute;left:0;top:5.9mm;width:36mm;text-align:center;font-family:'ArchivoBlack';font-size:10.5pt;line-height:1;color:#141414}
  .cat{left:8mm;top:43mm;width:36mm;height:14mm;border-radius:2mm;color:#FFFFFF;text-align:center}
  .cat b{display:block;font-family:'ArchivoBlack';font-weight:normal;font-size:19pt;line-height:1;margin-top:2mm}
  .cat small{display:block;font-family:'ArchivoXB';font-size:6.2pt;line-height:1;letter-spacing:.14em;text-transform:uppercase;margin-top:1mm}
  .gender{left:8mm;top:60mm;width:36mm;height:7.4mm;border-radius:2mm;background:#FFD60A;text-align:center;font-family:'ArchivoBlack';font-size:9.5pt;line-height:6.2mm;letter-spacing:.06em;text-transform:uppercase;color:#141414}
  .gender.f{background:#FF8FB8}
  .qr{left:8mm;top:69.5mm;width:20mm;height:20mm}
  .qrl{left:29.5mm;top:72mm;width:15mm;font-family:'ArchivoXB';font-size:5.6pt;line-height:1.25;letter-spacing:.12em;text-transform:uppercase;color:#6A6A6A}
  .qrl b{display:block;font-family:'ArchivoBlack';font-weight:normal;font-size:9pt;letter-spacing:0;color:#141414;margin-top:1mm}

  /* Tengah: nomor, nama, sponsor */
  .num{left:47mm;top:4mm;width:104mm;text-align:center;font-family:'ArchivoBlack';line-height:1;letter-spacing:-.02em}
  .name{left:47mm;width:104mm;text-align:center;font-family:'ArchivoBlack';line-height:1.05;text-transform:uppercase;color:#141414}
  .spons{left:47mm;top:62mm;width:104mm;height:25mm;border:.35mm solid #D4D4D4;border-radius:2.4mm}
  .spons .lbl{position:absolute;left:0;top:1.6mm;width:104mm;text-align:center;font-family:'ArchivoXB';font-size:5pt;letter-spacing:.2em;text-transform:uppercase;color:#9A9A9A}
  .spons .row{position:absolute;left:2mm;width:100mm;text-align:center}
  .spons img{margin:0 1.3mm;vertical-align:middle}
  .event{left:47mm;top:69mm;width:104mm;text-align:center}
  .event b{display:block;font-family:'ArchivoBlack';font-weight:normal;font-size:14pt;text-transform:uppercase;color:#141414}
  .event small{display:block;font-family:'ArchivoXB';font-size:7pt;letter-spacing:.16em;text-transform:uppercase;color:#E53935;margin-top:1.4mm}

  /* Kolom kanan */
  .info{left:154mm;top:6mm;width:49mm}
  .info table{border-collapse:collapse;width:49mm}
  .info td{padding:0 0 1.6mm 0;vertical-align:middle}
  .info td.l{text-align:right;padding-right:2mm;font-family:'JakartaSB';font-size:7.2pt;color:#3A3A3A;white-space:nowrap}
  .info td.v{width:15mm}
  .info .bx{width:15mm;height:5.8mm;border:.35mm solid #141414;border-radius:1mm;text-align:center;font-family:'ArchivoBlack';font-size:9.5pt;line-height:4.8mm;color:#141414}
  .fam{left:154mm;top:42mm;width:49mm;height:21mm;border:.4mm solid #141414;border-radius:2mm}
  .fam .t{position:absolute;left:0;top:0;width:49mm;height:5mm;background:#141414;color:#FFFFFF;text-align:center;font-family:'ArchivoBlack';font-size:6.6pt;line-height:4.1mm;letter-spacing:.1em}
  .fam .n{position:absolute;left:2.4mm;top:6.8mm;width:44mm;font-family:'JakartaSB';font-size:7.6pt;line-height:1.15;text-transform:uppercase;color:#3A3A3A}
  .fam .p{position:absolute;left:2.4mm;top:12.6mm;width:44mm;font-family:'ArchivoBlack';font-size:12.5pt;line-height:1;color:#141414}
  .runger{left:154mm;top:70mm;height:6.4mm}
  .edition{left:154mm;top:79mm;width:49mm;font-family:'ArchivoXB';font-size:5.8pt;letter-spacing:.14em;text-transform:uppercase;color:#E53935}

  /* Lubang peniti & panel */
  .pin{width:4.2mm;height:4.2mm;border-radius:2.1mm;border:.35mm dashed #A0A0A0}
  .pin.lt{border-color:#FFFFFF}
  .panel{left:0;width:210mm}
</style>
</head>
<body>
@foreach($regs as $r)
  @php
    $c = $r->category;
    $th = $themes[$c->id];
    $bib = (string) ($r->bib_number ?: '----');
    $numSize = strlen($bib) <= 3 ? 118 : (strlen($bib) === 4 ? 104 : 84);
    $name = mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $r->full_name)));
    $len = mb_strlen($name);
    $nameSize = $len <= 16 ? 21 : ($len <= 22 ? 17 : ($len <= 30 ? 13.5 : 11));
    $female = $r->gender === 'Perempuan';
    $blood = $r->blood_type && $r->blood_type !== '-' ? strtoupper($r->blood_type) : '';
    $rows = $spCount > 7 ? array_chunk($sponsors, (int) ceil($spCount / 2)) : [$sponsors];
  @endphp
  <div class="bib {{ $loop->last ? 'last' : '' }}">
    {{-- Kolom kiri --}}
    <img class="abs logo" src="{{ $logos['gtr'] }}" alt="">
    <div class="abs sos"><div class="t">SOS | PANITIA</div><div class="p">{{ $raceContact ?: '—' }}</div></div>
    <div class="abs cat" style="background:{{ $th['ink'] }}"><b>{{ $c->distance ?: $c->name }}</b>@if($c->tag)<small>{{ $c->tag }}</small>@endif</div>
    @if($r->gender)<div class="abs gender {{ $female ? 'f' : '' }}">{{ $female ? 'Perempuan' : 'Laki-laki' }}</div>@endif
    {{-- QR = nomor BIB persis (scanner timing & racepack) --}}
    @if($qr = B::qr($bib))
      <img class="abs qr" src="{{ $qr }}" alt="">
      <div class="abs qrl">Timing<b>{{ $bib }}</b></div>
    @endif

    {{-- Tengah --}}
    <div class="abs num" style="font-size:{{ $numSize }}pt;color:{{ $th['ink'] }}">{{ $bib }}</div>
    <div class="abs name" style="top:{{ $len <= 22 ? 49 : 51 }}mm;font-size:{{ $nameSize }}pt">{{ $name }}</div>
    @if($spCount)
      <div class="abs spons">
        <div class="lbl">Official Sponsors</div>
        @foreach($rows as $ri => $row)
          <div class="row" style="top:{{ count($rows) > 1 ? 5.6 + $ri * ($spH + 1.6) : 9 }}mm">
            @foreach($row as $sp)<img src="{{ $sp['path'] }}" alt="" style="{{ $spH * $sp['ratio'] > $spMaxW ? 'width:' . round($spMaxW, 2) . 'mm;height:' . round($spMaxW / $sp['ratio'], 2) : 'height:' . $spH . 'mm;width:' . round($spH * $sp['ratio'], 2) }}mm">@endforeach
          </div>
        @endforeach
      </div>
    @else
      <div class="abs event"><b>{{ $event }}</b>@if($eyebrow)<small>{{ $eyebrow }}</small>@endif</div>
    @endif

    {{-- Kolom kanan --}}
    <div class="abs info">
      <table>
        <tr><td class="l">Ukuran Jersey</td><td class="v"><div class="bx">{{ $r->size ?: '' }}</div></td></tr>
        <tr><td class="l">Gol. Darah</td><td class="v"><div class="bx">{{ $blood }}</div></td></tr>
        <tr><td class="l">Medali</td><td class="v"><div class="bx"></div></td></tr>
        <tr><td class="l">Refreshment</td><td class="v"><div class="bx"></div></td></tr>
      </table>
    </div>
    <div class="abs fam">
      <div class="t">KONTAK KELUARGA</div>
      <div class="n">{{ $r->emergency_name ?: '—' }}</div>
      <div class="p">{{ B::phone($r->emergency_contact) ?: '—' }}</div>
    </div>
    <img class="abs runger" src="{{ $logos['runger'] }}" alt="">
    @if($eyebrow)<div class="abs edition">{{ $eyebrow }}</div>@endif

    {{-- Panel elevasi --}}
    <img class="abs panel" src="{{ $panels[$c->id] }}" alt="" style="top:{{ $panelTop }}mm;height:{{ B::PANEL_H }}mm">

    {{-- Lubang peniti --}}
    <div class="abs pin" style="left:3.2mm;top:3.2mm"></div>
    <div class="abs pin" style="right:3.2mm;top:3.2mm"></div>
    <div class="abs pin lt" style="left:3.2mm;top:140.6mm"></div>
    <div class="abs pin lt" style="right:3.2mm;top:140.6mm"></div>
  </div>
@endforeach
</body>
</html>
