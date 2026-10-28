{{--
  Dokumen peserta GTR (A4) untuk dompdf — hanya CSS 2.1: tata letak memakai tabel, bukan flex/grid.
  Font dibedakan per keluarga (dompdf hanya mengenal normal/bold).
--}}
@php
  use App\Support\GtrDocumentPdf as D;
  // $body sudah diubah ke HTML ramah dompdf oleh App\Support\GtrDocumentHtml::toPdf().
  $en = $lang === 'en';
  $pt = fn ($v) => round($v * $k, 2) . 'pt';
  $font = fn ($f) => resource_path('fonts/' . $f);
  $meta = D::lines($en ? ($doc->meta_en ?: $doc->meta_id) : $doc->meta_id);
  $footer = $en ? ($doc->footer_en ?: $doc->footer_id) : $doc->footer_id;
  $ridge = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="1000" height="60" viewBox="0 0 1000 60" preserveAspectRatio="none"><path d="M0 26 L80 12 L150 20 L240 4 L310 18 L390 10 L480 26 L560 6 L640 20 L720 2 L800 16 L880 10 L1000 22 L1000 60 L0 60 Z" fill="#FBE3E2"/><path d="M0 36 L100 24 L180 32 L270 18 L350 30 L440 22 L540 36 L620 20 L710 32 L790 19 L890 30 L1000 26 L1000 60 L0 60 Z" fill="#141414"/><path d="M0 36 L100 24 L180 32 L270 18 L350 30 L440 22 L540 36 L620 20 L710 32 L790 19 L890 30 L1000 26" fill="none" stroke="#E53935" stroke-width="3"/></svg>');
@endphp
<!doctype html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<title>{{ $event }} — {{ $doc->title($lang) }}</title>
<style>
  @font-face{font-family:'ArchivoBlack';src:url('{{ $font('Archivo-900.ttf') }}') format('truetype');font-weight:normal;font-style:normal}
  @font-face{font-family:'ArchivoXB';src:url('{{ $font('Archivo-800.ttf') }}') format('truetype');font-weight:normal;font-style:normal}
  @font-face{font-family:'ArchivoXBI';src:url('{{ $font('Archivo-800i.ttf') }}') format('truetype');font-weight:normal;font-style:normal}
  @font-face{font-family:'Jakarta';src:url('{{ $font('PlusJakartaSans-400.ttf') }}') format('truetype');font-weight:normal;font-style:normal}
  @font-face{font-family:'Jakarta';src:url('{{ $font('PlusJakartaSans-700.ttf') }}') format('truetype');font-weight:bold;font-style:normal}
  @font-face{font-family:'Jakarta';src:url('{{ $font('PlusJakartaSans-400i.ttf') }}') format('truetype');font-weight:normal;font-style:italic}
  @font-face{font-family:'JakartaSB';src:url('{{ $font('PlusJakartaSans-600.ttf') }}') format('truetype');font-weight:normal;font-style:normal}

  @page{margin:12mm 14mm 17mm 14mm}
  /* Jangan reset margin lewat * atau html: di dompdf keduanya ikut menghapus margin @page. */
  body,div,h1,h2,p,table,td,span,img{margin:0;padding:0}
  body{font-family:'Jakarta';font-size:{{ $pt(7.6) }};line-height:1.38;color:#202020}

  /* Elemen tetap di tiap halaman */
  .topbar{position:fixed;top:-12mm;left:-14mm;right:-14mm;height:3mm;background:#E53935}
  .wm{position:fixed;top:72mm;left:31mm;width:120mm;opacity:.05}
  .foot{position:fixed;bottom:-17mm;left:-14mm;right:-14mm;height:15mm}
  .foot img{position:absolute;top:0;left:0;width:210mm;height:9mm}
  .foot .band{position:absolute;top:8.9mm;left:0;width:210mm;height:6.1mm;background:#141414}
  .foot table{position:absolute;top:9.9mm;left:14mm;width:182mm;border-collapse:collapse}
  .foot td{font-family:'ArchivoXB';font-size:6.4pt;letter-spacing:.12em;text-transform:uppercase;color:#fff;white-space:nowrap}
  .foot td.r{text-align:right;color:#BDBDBD;width:46mm;padding-left:4mm}

  /* Header */
  table{border-collapse:collapse;width:100%}
  td{vertical-align:top}
  .hd td.logo{width:50mm;text-align:right}
  .lang{display:inline-block;background:#141414;color:#fff;font-family:'ArchivoXB';font-size:7pt;letter-spacing:.16em;text-transform:uppercase;padding:1mm 2.4mm;border-radius:1mm}
  h1{font-family:'ArchivoBlack';font-weight:normal;font-size:21pt;line-height:1;text-transform:uppercase;margin-top:2.6mm;color:#141414}
  h1 span{color:#E53935}
  h2{font-family:'ArchivoXBI';font-weight:normal;font-size:12pt;line-height:1.15;margin-top:1.4mm;color:#141414}
  .chips{margin-top:2.6mm}
  .chip{display:inline-block;font-family:'ArchivoXB';font-size:5.8pt;letter-spacing:.04em;text-transform:uppercase;color:#4A4A4A;border:.25mm solid #D4D4D4;border-radius:3mm;padding:.7mm 2mm;margin:0 .8mm .8mm 0}
  .logos img.gtr{height:14mm}
  .logos img.runger{height:5.6mm;margin-left:2.6mm;padding-left:2.6mm;border-left:.3mm solid #D4D4D4}
  .eyebrow{font-family:'ArchivoXB';font-size:6.4pt;letter-spacing:.16em;text-transform:uppercase;color:#E53935;margin-top:2mm}
  .rule{height:.35mm;background:#141414;margin:3.2mm 0 1.6mm}

  /* Isi dokumen (hasil editor) */
  .doc p{margin:0 0 {{ $pt(3) }}}
  .doc p.gap{height:{{ $pt(4) }};margin:0}
  .doc strong,.doc b{font-weight:bold}
  .doc em,.doc i{font-style:italic}
  .doc u{text-decoration:underline}
  .doc a{color:#E53935;text-decoration:none}
  .doc hr{border:0;border-top:.3mm solid #D4D4D4;margin:{{ $pt(5) }} 0}
  .h{font-family:'ArchivoBlack';font-size:{{ $pt(8.2) }};letter-spacing:.06em;text-transform:uppercase;color:#141414;border-left:1.2mm solid #E53935;padding-left:2mm;margin:{{ $pt(7) }} 0 {{ $pt(3) }}}
  .h3{font-family:'ArchivoBlack';font-size:{{ $pt(7) }};letter-spacing:.12em;text-transform:uppercase;color:#E53935;margin:{{ $pt(4) }} 0 {{ $pt(2) }}}
  .doc > .h:first-child,.doc > .h3:first-child,.c > .h:first-child,.box > .h3:first-child{margin-top:0}
  .st{margin:0 0 {{ $pt(3) }}}
  .st td{padding-top:{{ $pt(1.9) }}}
  .st td p{margin:0}
  .st td.n{width:6.4mm}
  .st .num{width:4.2mm;height:4.2mm;line-height:3.7mm;margin-top:-.2mm;border-radius:1mm;background:#E53935;color:#fff;font-family:'ArchivoBlack';font-size:{{ $pt(6.4) }};text-align:center}
  .st .dot{width:1.5mm;height:1.5mm;background:#E53935;margin-top:{{ $pt(4.2) }};margin-left:.6mm}
  .st td.t{text-align:justify}
  .fl{margin:0 0 {{ $pt(3.4) }}}
  .fl td{padding-top:{{ $pt(2) }}}
  .fl td.lb{white-space:nowrap;padding-right:2mm;font-family:'JakartaSB';color:#3A3A3A;width:1%}
  .fl td.ln{border-bottom:.25mm solid #9A9A9A}
  .bl{display:inline-block;height:3mm;border-bottom:.25mm solid #9A9A9A}
  .cb{display:inline-block;width:2.7mm;height:2.7mm;border:.25mm solid #141414;border-radius:.5mm;margin:0 1mm 0 1.6mm}
  .yn td.a .cb:first-child,.doc p > .cb:first-child,.fi > .cb:first-child{margin-left:0}
  .yn{margin:0 0 {{ $pt(1.2) }}}
  .yn td{padding:{{ $pt(1.2) }} 0 {{ $pt(1.4) }};border-bottom:.2mm dotted #D0D0D0}
  .yn td.a{white-space:nowrap;text-align:right;width:1%;padding-left:2mm;font-family:'JakartaSB'}
  .box{border:.3mm solid #141414;border-radius:2mm;padding:2.4mm 3.2mm 2.4mm;margin:{{ $pt(4) }} 0 {{ $pt(3) }}}
  .box p:last-child{margin-bottom:0}
  .box em{font-size:{{ $pt(6.4) }};color:#8A8A8A}
  .sb{height:{{ $pt(30) }};border:.25mm dashed #B5B5B5;border-radius:1.2mm;margin:0 0 {{ $pt(2) }}}
  .cols{margin:0;table-layout:fixed}
  .cols td.c > .box:first-child{margin-top:0}
  .cols .st td.t,.cols p{text-align:left !important}
  .og{margin:0 0 {{ $pt(3) }};table-layout:fixed}
  .og td{padding:{{ $pt(1.4) }} 1mm {{ $pt(1.4) }} 0}
  .ut{margin:0 0 {{ $pt(3) }}}
  .ut td,.ut th{padding:{{ $pt(2) }} {{ $pt(4) }} {{ $pt(2) }} 0;text-align:left;vertical-align:top}
  .ut th{font-family:'JakartaSB';border-bottom:.25mm solid #9A9A9A}
  .ut td p,.ut th p{margin:0}
</style>
</head>
<body>
  <div class="topbar"></div>
  <img class="wm" src="{{ public_path('assets/gtr/logo.png') }}" alt="">
  <div class="foot">
    <img src="{{ $ridge }}" alt="">
    <div class="band"></div>
    <table><tr><td>{{ $footer }}</td><td class="r">Runners Gerung · Runger</td></tr></table>
  </div>

  <table class="hd"><tr>
    <td>
      <span class="lang">[{{ $en ? 'English' : 'Bahasa Indonesia' }}]</span>
      @php
        $title = trim($event);
        $year = preg_match('/^(.*?)\s+(\d{4})$/', $title, $m) ? [$m[1], $m[2]] : [$title, null];
      @endphp
      <h1>{{ $year[0] }}@if($year[1]) <span>{{ $year[1] }}</span>@endif</h1>
      <h2>{{ $doc->title($lang) }}</h2>
      @if($meta)<div class="chips">@foreach($meta as $m)<span class="chip">{{ $m }}</span>@endforeach</div>@endif
    </td>
    <td class="logo">
      <div class="logos"><img class="gtr" src="{{ public_path('assets/gtr/logo.png') }}" alt=""><img class="runger" src="{{ public_path('assets/gtr/logo-dark.png') }}" alt=""></div>
      @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
    </td>
  </tr></table>
  <div class="rule"></div>

  <div class="doc">{!! $body !!}</div>
</body>
</html>
