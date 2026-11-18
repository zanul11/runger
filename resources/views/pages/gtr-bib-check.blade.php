{{--
  Kiosk Cek BIB (publik, tanpa login) — dipasang di layar/laptop + mesin QR scanner USB/Bluetooth.
  Mesin scanner bekerja sebagai KEYBOARD (mode HID): mengetik isi QR (nomor BIB) lalu Enter.
  Halaman menangkap ketikan di mana pun fokusnya, mencari BIB di roster lokal, lalu menampilkan
  kartu BIB peserta dan kembali ke layar "Scan" otomatis.

  Desain kartu: "BIB Check Gerung Trail Run" (1000 × 500 px, turunan BIB v2) — dibangun di lebar
  aslinya (1000) lalu diskalakan memenuhi layar dari tepi ke tepi; tinggi kartu mengikuti rasio
  layar (bagian tengah memanjang, pita sponsor tetap di dasar). Palet per kategori: merah (15K) / hijau (7K); kategori
  lain memakai warna kategori dari admin.

  Kiosk-proof:
   - overlay "Scanner tidak aktif" saat jendela kehilangan fokus (ketikan scanner tak lagi masuk);
   - suara: ding (BIB terdaftar), buzz 3× (tidak terdaftar), nada tunggal (kode lain) — tombol Suara;
   - offline penuh: font lokal + service worker (sw.js) menyimpan halaman, data, font, & logo.

  Mode 'result' (/gerung-trail-run/cek-hasil, GtrResultCheckController): kartu yang sama, isinya panel hasil
  lomba: waktu resmi (gun time) besar selebar kartu, di bawahnya nomor BIB + nama + gender dan gambar rute
  kategori ala Strava (dari route_points). Peringkat, net time & pace tidak ditampilkan.
  Kartu hanya menampilkan waktu (tanpa label status / "hasil sementara"). Sebelum hasil dipublikasikan,
  hanya finisher yang tampil.

  Mode kamera (tanpa mesin scanner): tombol "Kamera" → webcam bawaan / kamera USB membaca QR di BIB
  (qr-scanner lokal; butuh HTTPS atau localhost). Bila ada >1 kamera muncul pilihan kamera; kamera
  eksternal dipilih otomatis saat pertama kali. Status & pilihan diingat per browser.

  Uji tanpa mesin: ketik nomor BIB + Enter, atau buka ?bib=7001.
--}}
@php($isResult = $mode === 'result')
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex">
<meta name="theme-color" content="#efede8">
<title>{{ $isResult ? 'Cek Hasil' : 'Cek BIB' }} — {{ $title }}</title>
<link rel="icon" type="image/jpeg" href="{{ asset('assets/gtr/logo.jpeg') }}">
{{-- Font lokal (bukan Google Fonts) supaya tetap tampil saat offline. --}}
<link rel="preload" href="{{ asset('assets/gtr/bib-check/fonts/barlowc-900i-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('assets/gtr/bib-check/fonts/barlowc-800-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@foreach($fontFaces as $f)
  @font-face{font-family:'{{ $f['family'] }}';font-style:{{ $f['style'] }};font-weight:{{ $f['weight'] }};font-display:swap;src:url('{{ $f['url'] }}') format('woff2');unicode-range:{{ $f['range'] }}}
@endforeach
</style>
@verbatim
<style>
  :root{
    --canvas:#efede8; --paper:#fbfaf8; --ink:#1c1414; --mute:rgba(28,20,20,.55); --line:rgba(28,20,20,.12);
    --red:#b81d27; --red-dark:#7d1219; --amber:#c77d0a; --ok:#2f8a4f;
    --cond:'Barlow Condensed',sans-serif;
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html,body{height:100%}
  body{background:var(--canvas);color:var(--ink);font-family:Barlow,system-ui,sans-serif;-webkit-font-smoothing:antialiased;
    overflow:hidden;user-select:none;cursor:default}
  .kiosk{height:100dvh;display:grid;grid-template-rows:1fr auto;padding:clamp(14px,2.2vw,32px);gap:clamp(10px,1.6vw,20px)}

  .stage{position:relative;min-height:0;display:grid}
  .view{grid-area:1/1;display:none;min-height:0}
  .view.on{display:grid;animation:in .3s cubic-bezier(.2,.8,.2,1)}
  @keyframes in{from{opacity:0;transform:scale(.975)}to{opacity:1;transform:none}}

  /* Logo GTR + pil Runger | Bukit Keteri (sama dengan kepala kartu BIB) */
  .brand{display:flex;align-items:center;gap:12px}
  .brand .gtr{width:140px;height:52px;object-fit:contain;display:block}
  /* Logo Runger tanpa kartu: logo putih transparan dipakai sebagai mask, diwarnai biru Runger
     (Bukit Keteri pindah ke pita sponsor bawah). */
  .cobrand{flex:none;width:136px;height:34px;background:#0540a7;
    -webkit-mask:var(--rg-logo) center/contain no-repeat;mask:var(--rg-logo) center/contain no-repeat}

  /* ------------------------------------------------------------ Idle --- */
  .idle{place-items:center;align-content:center;text-align:center;gap:clamp(14px,3vh,34px)}
  .idle .brand{transform:scale(clamp(1,.11vw + .9,1.6));transform-origin:center;margin-bottom:clamp(4px,2vh,24px)}
  .qr{width:clamp(110px,18vh,210px);aspect-ratio:1;position:relative;color:var(--red)}
  .qr svg{width:100%;height:100%}
  .qr::after{content:"";position:absolute;left:6%;right:6%;height:4px;border-radius:4px;background:var(--red);
    box-shadow:0 0 22px 3px rgba(184,29,39,.45);top:10%;animation:sweep 2.2s ease-in-out infinite alternate}
  @keyframes sweep{to{top:88%}}
  .kicker{font:700 15px/1 var(--cond);letter-spacing:.32em;color:#9e1922;display:flex;align-items:center;justify-content:center;gap:10px}
  .kicker span{width:26px;height:1.5px;background:#ecc9c5}
  .idle .kicker{font-size:clamp(14px,1.3vw,20px)}
  .idle h1{font:italic 900 clamp(64px,10vw,180px)/.85 var(--cond);color:var(--red);letter-spacing:-.01em;text-transform:uppercase}
  .idle p{font:500 clamp(18px,1.8vw,30px)/1.3 var(--cond);letter-spacing:.04em;color:var(--mute);text-transform:uppercase}

  /* ------------------------------------------- Mode kamera (opsional) --- */
  /* Kamera aktif: ikon QR di layar scan diganti pratinjau kamera (dicerminkan seperti kaca).
     Layar scan tetap dirender (visibility) saat kartu tampil supaya video & scan terus berjalan. */
  .camview{display:none;position:relative;height:clamp(180px,36vh,440px);aspect-ratio:4/3;border-radius:18px;overflow:hidden;
    background:#1c1414;box-shadow:0 18px 40px -22px rgba(0,0,0,.6),0 0 0 3px var(--red);transition:box-shadow .2s}
  .camview video{width:100%;height:100%;object-fit:cover;display:block;transform:scaleX(-1)}
  .camview .frame{position:absolute;inset:14%;border-radius:12px;pointer-events:none;
    box-shadow:0 0 0 999px rgba(28,20,20,.28);outline:3px dashed rgba(255,255,255,.75);outline-offset:-3px}
  .camview .frame::after{content:"";position:absolute;left:4%;right:4%;height:3px;border-radius:3px;background:var(--red);
    box-shadow:0 0 18px 3px rgba(184,29,39,.55);top:6%;animation:sweep 2.2s ease-in-out infinite alternate}
  .camview .msg{position:absolute;inset:0;display:grid;place-items:center;padding:20px;text-align:center;background:rgba(28,20,20,.82);
    color:#fff;font:700 clamp(16px,1.6vw,24px)/1.25 var(--cond);letter-spacing:.06em;text-transform:uppercase}
  .camview .msg:empty{display:none}
  .camview.hit{box-shadow:0 18px 40px -22px rgba(0,0,0,.6),0 0 0 6px var(--ok)}
  .cam-on .camview{display:block}
  .cam-on .idle .qr{display:none}
  .cam-on #v-idle:not(.on){display:grid;visibility:hidden}
  .camsel{max-width:220px;text-overflow:ellipsis}
  .camsel[hidden]{display:none}

  /* ---------------------------------------------- Kartu BIB (1000×500) --- */
  /* Kartu hasil menutupi seluruh layar (di atas padding kiosk & bilah bawah). */
  #v-ok{position:fixed;inset:0;z-index:5;background:var(--paper);place-items:center}
  .cardbox{position:relative;overflow:hidden}
  .bib{position:absolute;left:0;top:0;width:1000px;height:500px;transform-origin:0 0;display:flex;flex-direction:column;
    background:var(--paper);overflow:hidden;font-family:Barlow,sans-serif;color:var(--ink)}
  .terrain{position:absolute;left:0;top:0;display:block;pointer-events:none}
  .terrain .topo{stroke:var(--c);stroke-opacity:.13;stroke-width:1.2;fill:none}
  .terrain .r3{fill:var(--c);fill-opacity:.07}
  .terrain .r2{fill:var(--c);fill-opacity:.13}
  .terrain .r1{fill:var(--r1)}
  .bib-head{flex:none;position:relative;display:grid;grid-template-columns:1fr auto 1fr;align-items:start;padding:0 44px}
  .bib-head .brand{padding-top:24px}
  .bib-head .kicker{padding-top:30px;color:var(--label)}
  .bib-head .kicker span{background:var(--rule)}
  .cat{justify-self:end;display:flex;align-items:stretch;border-radius:0 0 4px 4px;overflow:hidden}
  .cat .nm{background:var(--c);color:#fff;font:italic 900 40px/1 var(--cond);padding:10px 12px 4px}
  .cat .tg{background:var(--dark);color:#fff;font:italic 900 31px/1 var(--cond);letter-spacing:.01em;display:flex;align-items:center;
    padding:4px 14px 0;white-space:nowrap;text-transform:uppercase}
  .cat .tg:empty{display:none}
  .bib-body{flex:1;min-height:0;position:relative;display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:6px;text-align:center;padding:0 44px 30px}
  .bib-num{font:italic 900 250px/.78 var(--cond);color:var(--c);letter-spacing:-.01em;white-space:nowrap}
  .bib-name{font:800 48px/1.05 var(--cond);letter-spacing:.03em;text-transform:uppercase;text-wrap:balance;max-width:912px;overflow-wrap:anywhere}
  /* Gender (MALE/FEMALE) di bawah nama, lebih kecil dari nama. Blok BIB+nama+gender diangkat
     otomatis (liftAboveRidge) bila terlalu dekat puncak bukit; tepi warna kertas sebagai cadangan. */
  .bib-who{display:flex;flex-direction:column;align-items:center;gap:8px;max-width:912px}
  .bib-who .bib-name{min-width:0}
  .bib-gender{flex:none;font:italic 900 26px/1 var(--cond);color:#fff;background:var(--c);border-radius:4px;
    padding:.16em .6em .04em;min-width:1.6em;text-align:center;box-shadow:0 0 0 3px var(--paper);
    text-transform:uppercase;letter-spacing:.06em}
  .bib-gender:empty{display:none}
  .bib-band{flex:none;height:78px;position:relative;overflow:hidden;background:linear-gradient(180deg,var(--g1) 0%,var(--g2) 100%);
    display:flex;align-items:center;justify-content:center;padding:0 44px}
  .bib-band .wm{position:absolute;right:-6px;top:-34px;font:italic 900 150px/1 var(--cond);color:rgba(255,255,255,.07);white-space:nowrap}
  /* Sponsor di pita putih (senada pil Runger | Keteri) — logo berwarna tetap kontras di band merah/hijau. */
  .sponsors{position:relative;display:flex;align-items:center;gap:28px;height:58px;padding:0 28px;background:#fff;
    border-radius:14px;box-shadow:0 10px 22px -12px rgba(0,0,0,.55),0 0 0 1px rgba(0,0,0,.05)}
  .sponsors div{flex:none;height:40px;display:flex;align-items:center}
  .sponsors img{height:100%;max-width:124px;object-fit:contain;display:block}
  .sponsors .wide{height:36px}

  /* Kiosk BIB: blok BIB + nama di tengah, nomor BIB paling besar.
     Kiosk hasil: satu panel selebar kartu — WAKTU besar penuh di atas, nomor BIB + nama + gambar rute
     di baris bawahnya. */
  .bib-id{display:flex;flex-direction:column;align-items:center;gap:6px;min-width:0}
  .res{display:none}
  .mode-result .res{display:flex}
  .mode-result .bib-id{flex:1 1 280px;flex-direction:row;align-items:center;gap:calc(var(--k,1)*18px);text-align:left}
  .mode-result .bib-who{align-items:flex-start;gap:calc(var(--k,1)*6px);max-width:none}
  .mode-result .bib-name{max-width:none}

  /* ---------------------------------------------------- Panel hasil lomba --- */
  /* Ukuran dalam unit desain × --k (kartu lebih tinggi dari desain → ikut membesar, maks. 1,3×). */
  .res{position:relative;flex:none;width:100%;min-width:0;flex-direction:column;gap:calc(var(--k,1)*12px);text-align:left;
    padding:calc(var(--k,1)*18px) calc(var(--k,1)*22px);border-radius:14px;background:var(--paper);
    box-shadow:0 16px 34px -20px rgba(0,0,0,.45),0 0 0 1.5px var(--rule)}
  #x-fin{display:flex;justify-content:center}
  .res-time{font:italic 900 calc(var(--k,1)*240px)/.82 var(--cond);color:var(--ink);letter-spacing:-.01em;
    font-variant-numeric:tabular-nums;white-space:nowrap;margin-top:calc(var(--k,1)*4px);width:max-content}
  /* Baris bawah: nomor BIB + nama + gender (kiri) · gambar rute (kanan); turun ke baris baru bila sempit. */
  .res-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:calc(var(--k,1)*10px) calc(var(--k,1)*24px);
    border-top:1.5px solid var(--rule);padding-top:calc(var(--k,1)*12px)}
  /* Gambar rute ala Strava (path dari data kategori; ukuran diset fitRoute()). Tebal garis dalam px layar. */
  .res-route{flex:none;display:block;overflow:visible;filter:drop-shadow(0 3px 4px rgba(0,0,0,.18))}
  .res-route path{fill:none;stroke-linecap:round;stroke-linejoin:round;vector-effect:non-scaling-stroke}
  .res-route .case{stroke:#fff;stroke-width:calc(var(--k,1)*11px)}
  .res-route .line{stroke:var(--c);stroke-width:calc(var(--k,1)*6px)}
  .res-route circle{stroke:#fff;vector-effect:non-scaling-stroke;stroke-width:calc(var(--k,1)*3px)}
  .res-route .fin{fill:var(--ink)} .res-route .start{fill:var(--ok)}
  #x-none{text-align:center}
  .res-msg{font:800 calc(var(--k,1)*54px)/.92 var(--cond);text-transform:uppercase;color:var(--ink);text-wrap:balance}
  .res-sub{margin-top:calc(var(--k,1)*10px);font:500 calc(var(--k,1)*19px)/1.3 var(--cond);letter-spacing:.03em;text-transform:uppercase;color:var(--mute)}
  .res [hidden]{display:none}

  /* Bilah hitung mundur kembali ke layar scan (tepi bawah pita sponsor) */
  .timer{position:absolute;left:0;right:0;bottom:0;height:5px;background:rgba(255,255,255,.12);overflow:hidden;z-index:1}
  .timer i{display:block;height:100%;width:100%;background:rgba(255,255,255,.55);transform-origin:left}

  /* ----------------------------------------------------------- Gagal --- */
  .bad{place-items:center;align-content:center;text-align:center;gap:clamp(12px,2.4vh,26px)}
  .bad .ic{width:clamp(96px,15vh,170px);height:clamp(96px,15vh,170px);border-radius:50%;display:grid;place-items:center;
    background:rgba(184,29,39,.1);color:var(--red);border:3px solid rgba(184,29,39,.35)}
  .bad .ic svg{width:55%;height:55%}
  .bad.warn .ic{background:rgba(199,125,10,.1);color:var(--amber);border-color:rgba(199,125,10,.35)}
  .bad h2{font:italic 900 clamp(56px,8vw,140px)/.88 var(--cond);text-transform:uppercase;color:var(--ink)}
  .bad .code{font:italic 900 clamp(30px,3.6vw,64px)/1 var(--cond);color:var(--red);max-width:90vw;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .bad.warn .code{color:var(--amber)}
  .bad .code:empty{display:none}
  .bad p{font:500 clamp(18px,1.8vw,30px)/1.3 var(--cond);letter-spacing:.04em;text-transform:uppercase;color:var(--mute);max-width:40ch}

  /* ----------------------------------------------------------- Bawah --- */
  .foot{display:flex;align-items:center;gap:18px;font:500 clamp(12px,1vw,15px)/1.2 Barlow,sans-serif;color:var(--mute);min-height:28px}
  .typed{font:italic 900 clamp(18px,1.6vw,26px)/1 var(--cond);color:var(--ink);letter-spacing:.04em}
  .typed:empty::before{content:"Tanpa mesin? Ketik nomor BIB lalu Enter";font:500 clamp(12px,1vw,15px)/1.2 Barlow,sans-serif;color:var(--mute);letter-spacing:0}
  .status{margin-left:auto;display:flex;align-items:center;gap:8px;white-space:nowrap}
  .status i{width:9px;height:9px;border-radius:50%;background:var(--mute)}
  .status.ok i{background:var(--ok)} .status.stale i{background:var(--amber)} .status.err i{background:var(--red)}
  .clock{font:700 clamp(15px,1.3vw,20px)/1 var(--cond);letter-spacing:.06em;color:var(--ink);font-variant-numeric:tabular-nums}
  .fs{border:1px solid var(--line);background:none;color:var(--mute);border-radius:999px;padding:6px 14px;font:inherit;cursor:pointer}
  :fullscreen .fs{display:none}
  .fs.off{color:var(--red);border-color:rgba(184,29,39,.35)}

  /* ------------------------------------------- Overlay: fokus hilang --- */
  .lock{position:fixed;inset:0;z-index:50;display:grid;place-items:center;text-align:center;cursor:pointer;
    background:rgba(28,20,20,.86);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);color:#fff}
  .lock[hidden]{display:none}
  .lock .ring{width:clamp(110px,16vh,180px);height:clamp(110px,16vh,180px);margin:0 auto clamp(18px,3vh,34px);border-radius:50%;
    display:grid;place-items:center;background:var(--red);box-shadow:0 0 0 0 rgba(184,29,39,.6);animation:ping 1.6s ease-out infinite}
  .lock .ring svg{width:50%;height:50%}
  @keyframes ping{to{box-shadow:0 0 0 clamp(26px,4vh,48px) rgba(184,29,39,0)}}
  .lock h2{font:italic 900 clamp(56px,8vw,140px)/.88 var(--cond);text-transform:uppercase}
  .lock p{margin-top:clamp(10px,2vh,20px);font:600 clamp(20px,2.2vw,36px)/1.3 var(--cond);letter-spacing:.04em;text-transform:uppercase;color:rgba(255,255,255,.75)}

  @media (prefers-reduced-motion:reduce){ .view.on{animation:none} .qr::after,.camview .frame::after{animation:none;top:48%} .lock .ring{animation:none} }
</style>
@endverbatim
</head>
<body>
<main class="kiosk">
  <section class="stage" id="stage" aria-live="assertive">

    {{-- Idle: menunggu scan --}}
    <div class="view idle on" id="v-idle">
      <div class="brand">
        <img class="gtr" src="{{ asset('assets/gtr/bib-check/gtr-logo.webp') }}" alt="Gerung Trail Run">
        <div class="cobrand" role="img" aria-label="Runger" style="--rg-logo:url('{{ asset('assets/runger-logo-white.webp') }}')"></div>
      </div>
      <div class="kicker"><span></span>{{ $isResult ? 'RESULT CHECK' : 'BIB CHECK' }}<span></span></div>
      <div class="qr" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2"/>
          <rect x="7" y="7" width="3.5" height="3.5" rx=".5"/><rect x="13.5" y="7" width="3.5" height="3.5" rx=".5"/>
          <rect x="7" y="13.5" width="3.5" height="3.5" rx=".5"/><path d="M13.5 13.5h1.5v1.5M17 13.5v.01M13.5 17h.01M16 16v1h1"/>
        </svg>
      </div>
      <div class="camview" id="camview" aria-hidden="true">
        <video id="cam-video" muted playsinline autoplay></video>
        <div class="frame"></div>
        <div class="msg" id="cam-msg"></div>
      </div>
      @if($isResult)
        <h1>Cek hasil lari</h1>
        <p id="idle-hint" data-cam="Arahkan QR code di BIB ke kamera">Scan QR code di BIB untuk lihat waktu &amp; peringkatmu</p>
      @else
        <h1>Scan BIB kamu</h1>
        <p id="idle-hint" data-cam="Arahkan QR code di BIB ke kamera">Arahkan QR code di BIB ke mesin scanner</p>
      @endif
    </div>

    {{-- Hasil: kartu BIB --}}
    <div class="view" id="v-ok">
      <div class="cardbox" id="cardbox">
        <div class="bib{{ $isResult ? ' mode-result' : '' }}" id="card">
          <svg class="terrain" id="terrain" viewBox="0 0 1000 500" width="1000" height="500" aria-hidden="true">
            <path class="topo" id="t-topo"/>
            <g id="t-ridges"><path class="r3" id="t-r3"/><path class="r2" id="t-r2"/><path class="r1" id="t-r1"/></g>
          </svg>
          <div class="bib-head">
            <div class="brand">
              <img class="gtr" src="{{ asset('assets/gtr/bib-check/gtr-logo.webp') }}" alt="Gerung Trail Run">
              <div class="cobrand" role="img" aria-label="Runger" style="--rg-logo:url('{{ asset('assets/runger-logo-white.webp') }}')"></div>
            </div>
            <div class="kicker"><span></span>{{ $isResult ? 'RESULT CHECK' : 'BIB CHECK' }}<span></span></div>
            <div class="cat"><span class="nm" id="c-name"></span><span class="tg" id="c-tag"></span></div>
          </div>
          <div class="bib-body" id="bib-body">
            @if(! $isResult)
              <div class="bib-id" id="bib-id">
                <div class="bib-num" id="r-bib"></div>
                <div class="bib-who"><div class="bib-name" id="r-name"></div><div class="bib-gender" id="r-gender"></div></div>
              </div>
            @else
              {{-- Panel hasil: waktu penuh di atas, BIB + nama + gambar rute di bawahnya. Diisi renderResult() --}}
              <div class="res" id="res">
                <div id="x-fin"><div class="res-time" id="x-gun"></div></div>
                <div id="x-none">
                  <div class="res-msg" id="x-msg"></div>
                  <div class="res-sub" id="x-sub"></div>
                </div>
                <div class="res-foot">
                  <div class="bib-id" id="bib-id">
                    <div class="bib-num" id="r-bib"></div>
                    <div class="bib-who"><div class="bib-name" id="r-name"></div><div class="bib-gender" id="r-gender"></div></div>
                  </div>
                  <svg class="res-route" id="x-route" aria-hidden="true">
                    <path class="case" id="x-route-case"/><path class="line" id="x-route-line"/>
                    <circle class="fin" id="x-route-fin"/><circle class="start" id="x-route-start"/>
                  </svg>
                </div>
              </div>
            @endif
          </div>
          <div class="bib-band">
            <div class="wm" id="c-wm" aria-hidden="true"></div>
            <div class="sponsors">
              <div><img src="{{ asset('assets/gtr/bib-check/sponsor-alti.webp') }}" alt="ALTI NTB"></div>
              <div><img src="{{ asset('assets/gtr/bib-check/sponsor-lobar.webp') }}" alt="Pemkab Lombok Barat"></div>
              <div class="wide"><img src="{{ asset('assets/gtr/bib-check/sponsor-dispar.webp') }}" alt="Dispar Lombok Barat"></div>
              <div class="wide"><img src="{{ asset('assets/gtr/bib-check/bank-ntb-syariah.webp') }}" alt="Bank NTB Syariah"></div>
              <div><img src="{{ asset('assets/gtr/bib-check/pam.webp') }}" alt="PT. Air Minum Giri Menang"></div>
              <div><img src="{{ asset('assets/gtr/bib-check/pocari.webp') }}" alt="Pocari Sweat"></div>
              <div><img src="{{ asset('assets/gtr/bib-check/aqua.webp') }}" alt="AQUA"></div>
              <div><img src="{{ asset('assets/gtr/keteri.webp') }}" alt="Bukit Keteri"></div>
            </div>
            <div class="timer"><i id="timer-bar"></i></div>
          </div>
        </div>
      </div>
    </div>

    {{-- Gagal --}}
    <div class="view bad" id="v-bad">
      <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg></div>
      <h2 id="b-title">BIB tidak terdaftar</h2>
      <div class="code" id="b-code"></div>
      <p id="b-sub">Silakan hubungi meja informasi panitia</p>
    </div>
  </section>

  <footer class="foot">
    <span class="typed" id="typed"></span>
    <span class="status" id="status"><i></i><span id="status-text">Memuat data peserta…</span></span>
    <span class="clock" id="clock">--:-- WITA</span>
    <select class="fs camsel" id="cam-sel" aria-label="Pilih kamera" hidden></select>
    <button class="fs" id="cam" type="button">Kamera: OFF</button>
    <button class="fs" id="snd" type="button">Suara: ON</button>
    <button class="fs" id="fs" type="button">Layar penuh</button>
  </footer>
</main>

{{-- Jendela kehilangan fokus → ketikan mesin scanner tidak masuk ke halaman ini. --}}
<div class="lock" id="lock" role="alertdialog" aria-label="Scanner tidak aktif" hidden>
  <div>
    <div class="ring"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11V6a3 3 0 0 1 6 0v5"/><path d="M5 11h14v4a7 7 0 0 1-14 0z"/><path d="M12 14v3"/></svg></div>
    <h2>Scanner tidak aktif</h2>
    <p>Ketuk layar ini agar scan BIB terbaca</p>
  </div>
</div>

{{-- Decoder QR untuk mode kamera (lokal + di-precache SW; worker dimuat relatif terhadap file ini). --}}
<script src="{{ asset('assets/gtr/bib-check/qr/qr-scanner.umd.min.js') }}"></script>
<script>
(function () {
  var DATA_URL = @json($dataUrl);
  var SW_URL = @json($swUrl);
  var SW_SCOPE = @json($scope);
  var MODE = @json($mode);
@verbatim
  var IS_RESULT = MODE === 'result';
  var STORE_KEY = IS_RESULT ? 'gtr-resultcheck-roster-v1' : 'gtr-bibcheck-roster-v3';
  // Roster diperbarui tiap 5 menit; hasil lomba tiap 30 detik (pelari terus masuk finish).
  var REFRESH_MS = IS_RESULT ? 30 * 1000 : 5 * 60 * 1000;
  var SHOW_OK_MS = 30000;          // lama kartu tampil sebelum kembali ke layar scan
  var SHOW_BAD_MS = 7000;
  var SCAN_GAP_MS = 60;             // jeda antar-karakter mesin scanner (manusia jauh lebih lambat)
  var GENDER = { M: 'Male', F: 'Female' };

  var roster = null;
  var $ = function (id) { return document.getElementById(id); };

  // ---------------------------------------------------------------- data ----
  function store(data) { try { localStorage.setItem(STORE_KEY, JSON.stringify(data)); } catch (e) {} }
  function restore() { try { return JSON.parse(localStorage.getItem(STORE_KEY) || 'null'); } catch (e) { return null; } }

  function setStatus(cls, text) { $('status').className = 'status ' + cls; $('status-text').textContent = text; }
  function hhmm(ms) { return new Date(ms).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Makassar' }).replace('.', ':'); }

  // Offline = fetch gagal, browser offline, atau service worker menjawab dari cache (X-Gtr-Offline).
  var offline = false;
  function renderStatus() {
    if (!roster) return setStatus('err', 'Data peserta gagal dimuat — cek koneksi');
    var info = roster.count + ' peserta · ' + (IS_RESULT ? (roster.results || 0) + ' hasil' + (roster.published ? ' resmi' : ' sementara') + ' · ' : '') + 'data ' + hhmm(roster.generated_at_ms);
    if (offline || !navigator.onLine) setStatus('stale', 'Offline · ' + info); else setStatus('ok', info);
  }
  function load() {
    return fetch(DATA_URL, { cache: 'no-store', headers: { Accept: 'application/json' } })
      .then(function (r) {
        if (!r.ok) throw new Error(r.status);
        offline = r.headers.get('X-Gtr-Offline') === '1';
        return r.json();
      })
      .then(function (data) { roster = data; store(data); terrainCache = {}; })
      .catch(function () { offline = true; if (!roster) roster = restore(); })
      .then(renderStatus);
  }
  window.addEventListener('online', function () { load(); });
  window.addEventListener('offline', renderStatus);

  // Sama dengan resources/js/scanner/bib.js (normalizeBib): buang spasi, hanya digit, buang nol di depan.
  function normalizeBib(s) {
    s = String(s).replace(/[\s ﻿]+/g, '');
    if (!/^[0-9]+$/.test(s)) return null;
    s = s.replace(/^0+/, '');
    return s && s.length <= 8 ? s : null;
  }

  // ------------------------------------------------------ palet kategori ----
  // Palet persis dari desain; dipilih dari rona warna kategori di admin (hijau → 7K, merah → 15K).
  var PALETTES = {
    red:   { c: '#b81d27', dark: '#7d1219', r1: '#c8202b', g1: '#c8202b', g2: '#a5161f', label: '#9e1922', rule: '#ecc9c5', ink: '#1c1414', flip: false },
    green: { c: '#3f7356', dark: '#24452f', r1: '#4a8062', g1: '#4a8062', g2: '#33604a', label: '#2f5a43', rule: '#c4d9cb', ink: '#141c17', flip: true }
  };
  function hue(hex) {
    var m = /^#?([0-9a-f]{6})$/i.exec(hex || ''); if (!m) return null;
    var n = parseInt(m[1], 16), r = (n >> 16 & 255) / 255, g = (n >> 8 & 255) / 255, b = (n & 255) / 255;
    var mx = Math.max(r, g, b), mn = Math.min(r, g, b), d = mx - mn;
    if (!d) return null;
    var h = mx === r ? ((g - b) / d) % 6 : mx === g ? (b - r) / d + 2 : (r - g) / d + 4;
    return (h * 60 + 360) % 360;
  }
  function paletteFor(hex) {
    var h = hue(hex);
    if (h !== null && h >= 70 && h <= 175) return PALETTES.green;
    if (h === null || h <= 20 || h >= 335) return PALETTES.red;
    var mix = function (p, w) { return 'color-mix(in oklab, ' + hex + ' ' + p + '%, ' + w + ')'; };
    return { c: hex, dark: mix(60, 'black'), r1: hex, g1: hex, g2: mix(80, 'black'), label: mix(80, 'black'), rule: mix(25, 'white'), ink: '#1a1a1a', flip: false };
  }

  // -------------------------------------------- siluet bukit (dari desain) ----
  // Punggungan dari profil elevasi kategori + garis kontur topo, viewBox 1000×500, alas di y=423.
  var YB = 423;
  function ridge(ele, h, shift, flip) {
    var e = flip ? ele.slice().reverse() : ele, n = e.length - 1, mx = Math.max.apply(null, e) || 1;
    var pts = e.map(function (v, i) { return (i / n * 1040 - 20 + shift).toFixed(1) + ',' + (YB - v / mx * h).toFixed(1); });
    return 'M' + pts.join('L') + 'L' + (1020 + shift).toFixed(1) + ',' + YB + 'L' + (-20 + shift).toFixed(1) + ',' + YB + 'Z';
  }
  function topo(cx, cy, seed, rings, step) {
    var d = '';
    for (var k = 1; k <= rings; k++) {
      var r = k * step, s = '';
      for (var a = 0; a <= 72; a++) {
        var t = a / 72 * Math.PI * 2;
        var rr = r * (1 + 0.16 * Math.sin(3 * t + seed + k * 0.12) + 0.07 * Math.sin(5 * t - seed * 1.7) + 0.04 * Math.cos(7 * t + k * 0.3));
        s += (a ? 'L' : 'M') + (cx + rr * Math.cos(t) * 1.35).toFixed(1) + ',' + (cy + rr * Math.sin(t)).toFixed(1);
      }
      d += s + 'Z';
    }
    return d;
  }
  var FALLBACK_ELE = Array.from({ length: 120 }, function (_, i) { return 90 + 60 * Math.sin(i / 14) + 120 * Math.exp(-Math.pow((i - 70) / 18, 2)); });
  var terrainCache = {};
  /** H = tinggi kartu (≥ 500): kontur tetap di sudut atas & bawah, punggungan tetap menempel pita. */
  function terrain(id, ele, flip, H) {
    var key = id + (flip ? 'f' : '') + H;
    if (terrainCache[key]) return terrainCache[key];
    var e = ele && ele.length > 3 ? ele : FALLBACK_ELE;
    return (terrainCache[key] = {
      topo: topo(flip ? 140 : 860, 120, flip ? 2.1 : 0.7, 13, 22) + topo(flip ? 900 : 90, H - 140, flip ? 4.2 : 3.3, 11, 24),
      r3: ridge(e, 150, -60, !flip), r2: ridge(e, 105, 30, true), r1: ridge(e, 56, 0, false)
    });
  }

  // ---------------------------------------------------------------- view ----
  var backTimer;
  function show(id, ms) {
    ['v-idle', 'v-ok', 'v-bad'].forEach(function (v) { $(v).classList.toggle('on', v === id); });
    clearTimeout(backTimer);
    if (!ms) return;
    if (id === 'v-ok') {
      var bar = $('timer-bar');
      bar.style.transition = 'none'; bar.style.transform = 'scaleX(1)';
      void bar.offsetWidth;
      bar.style.transition = 'transform ' + ms + 'ms linear'; bar.style.transform = 'scaleX(0)';
    }
    backTimer = setTimeout(function () { show('v-idle'); }, ms);
  }

  /**
   * Kartu memenuhi layar: lebar desain 1000 diskalakan ke lebar layar, tinggi kartu = sisa rasio
   * layar (min. 500 = rasio desain 2:1; layar lebih pipih dari 2:1 → diskalakan menurut tinggi).
   * @returns {number} tinggi kartu dalam unit desain
   */
  var cardH = 500;
  function fitCard() {
    var W = window.innerWidth, Hs = window.innerHeight;
    var s = W / 1000, H = Hs / s;
    if (H < 500) { s = Hs / 500; H = 500; }
    H = Math.floor(H);
    var card = $('card');
    card.style.height = H + 'px';
    card.style.transform = 'scale(' + s + ')';
    $('cardbox').style.width = Math.round(1000 * s) + 'px';
    $('cardbox').style.height = Math.round(H * s) + 'px';
    $('terrain').setAttribute('viewBox', '0 0 1000 ' + H);
    $('terrain').setAttribute('height', H);
    $('t-ridges').setAttribute('transform', 'translate(0 ' + (H - 500) + ')');
    return (cardH = H);
  }
  window.addEventListener('resize', function () {
    if (!$('v-ok').classList.contains('on') || !current) return;
    showRunner(current, true);
  });

  /** Nomor BIB panjang & nama panjang diperkecil supaya tetap di dalam kartu (ukuran desain = maksimum). */
  function fitText() {
    // Kartu lebih tinggi dari desain → teks ikut membesar (maks. 1,3×), tetap dibatasi lebar kartu.
    var k = Math.min(1.3, cardH / 500);
    $('card').style.setProperty('--k', k.toFixed(3));
    var num = $('r-bib'), name = $('r-name'), gender = $('r-gender');
    // Kiosk hasil: nomor BIB kecil di baris bawah — yang ditonjolkan waktunya.
    var maxW = IS_RESULT ? 260 : 912, nf = Math.round((IS_RESULT ? 72 : 250) * k);
    num.style.fontSize = nf + 'px';
    if (num.offsetWidth > maxW) num.style.fontSize = Math.floor(nf * maxW / num.offsetWidth) + 'px';
    // Gender di bawah nama, ±55% ukuran nama.
    var fs = Math.round((IS_RESULT ? 32 : 48) * k);
    var setName = function (f) { name.style.fontSize = f + 'px'; gender.style.fontSize = Math.round(f * .55) + 'px'; };
    setName(fs);
    while (name.offsetHeight > fs * 1.05 * 2 + 4 && fs > (IS_RESULT ? 18 : 28)) { fs -= 2; setName(fs); }
    if (IS_RESULT) fitRoute(k);
    // Waktu resmi dibuat sebesar mungkin: selebar panel (maks. 340 × k), lalu diperkecil
    // bila panel jadi lebih tinggi dari ruang di antara kepala kartu & pita sponsor.
    var gun = $('x-gun');
    if (gun && gun.offsetParent) {
      gun.style.fontSize = '';
      var res = $('res'), body = $('bib-body'), gf = parseFloat(getComputedStyle(gun).fontSize);
      gf = Math.min(340 * k, gf * (res.clientWidth - 44 * k) * .95 / gun.offsetWidth);   // .95: sisa ruang untuk kemiringan italic
      gun.style.fontSize = Math.floor(gf) + 'px';
      var over = res.offsetHeight - (body.clientHeight - parseFloat(getComputedStyle(body).paddingBottom) - 16 * k);
      if (over > 0) gun.style.fontSize = Math.max(60, Math.floor(gf - over / .82)) + 'px';
    }
  }

  /**
   * Angkat blok BIB + nama + gender bila baris terakhirnya terlalu dekat punggungan bukit depan (r1)
   * tepat di bawahnya — mis. 7K, yang puncaknya di tengah kartu. Dibatasi ruang kosong di bawah kepala.
   */
  var RIDGE_GAP = 26;   // jarak aman (unit desain) antara teks terbawah & puncak bukit
  function liftAboveRidge(ele) {
    var body = $('bib-id');   // hanya blok BIB + nama
    body.style.transform = '';
    if (IS_RESULT) return;    // kiosk hasil: blok BIB ada di dalam panel berlatar kertas
    var cr = $('card').getBoundingClientRect(), s = cr.width / 1000;
    var last = $('r-gender').textContent ? $('r-gender') : $('r-name');
    var lr = last.getBoundingClientRect();
    var x0 = (lr.left - cr.left) / s - 20, x1 = (lr.right - cr.left) / s + 20, bottom = (lr.bottom - cr.top) / s;

    var e = ele && ele.length > 3 ? ele : FALLBACK_ELE, n = e.length - 1, mx = Math.max.apply(null, e) || 1, top = Infinity;
    e.forEach(function (v, i) {
      var x = i / n * 1040 - 20;
      if (x >= x0 - 10 && x <= x1 + 10) top = Math.min(top, YB - v / mx * 56);
    });
    if (top === Infinity) return;
    top += cardH - 500;   // punggungan digeser ke bawah pada kartu yang lebih tinggi dari desain

    var need = bottom + RIDGE_GAP - top;
    if (need <= 0) return;
    var nr = $('r-bib').getBoundingClientRect(), hr = document.querySelector('.bib-head').getBoundingClientRect();
    var room = Math.max(0, (nr.top - hr.bottom) / s - 4);
    body.style.transform = 'translateY(' + (-Math.min(need, room)).toFixed(1) + 'px)';
  }

  var current = null;
  function showRunner(r, keepTimer) {
    current = r;
    var H = fitCard();
    var cat = (roster.categories || {})[r.category_id] || {};
    var p = paletteFor(cat.color), card = $('card').style;
    ['c', 'dark', 'r1', 'g1', 'g2', 'label', 'rule', 'ink'].forEach(function (k) { card.setProperty('--' + k, p[k]); });
    document.documentElement.style.setProperty('--c', p.c);

    var t = terrain(r.category_id, cat.ele, p.flip, H);
    $('t-topo').setAttribute('d', t.topo); $('t-r3').setAttribute('d', t.r3);
    $('t-r2').setAttribute('d', t.r2); $('t-r1').setAttribute('d', t.r1);

    $('c-name').textContent = cat.name || '';
    $('c-tag').textContent = cat.tag || '';
    $('c-wm').textContent = cat.name || '';
    $('r-bib').textContent = r.bib;
    $('r-name').textContent = r.name || '—';
    $('r-gender').textContent = GENDER[r.gender] || '';
    var hasResult = IS_RESULT ? renderResult(r) : true;

    if (!keepTimer) { show('v-ok', SHOW_OK_MS); play(hasResult ? 'ok' : 'warn'); }
    fitText();
    liftAboveRidge(cat.ele);
  }

  /** Gambar rute kategori (ala Strava): garis warna kategori bertepi putih, titik start & finish. */
  function drawRoute(rt) {
    var svg = $('x-route');
    svg.hidden = !(rt && rt.d && rt.vb);
    if (svg.hidden) return;
    var vb = rt.vb.split(' ').map(Number), d = rt.d;
    svg.setAttribute('viewBox', rt.vb);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    $('x-route-case').setAttribute('d', d);
    $('x-route-line').setAttribute('d', d);
    var nums = d.replace(/[ML]/g, ' ').trim().split(/\s+/).map(Number), n = nums.length;
    var dot = Math.max(vb[2], vb[3]) * .035;
    [['x-route-start', nums[0], nums[1]], ['x-route-fin', nums[n - 2], nums[n - 1]]].forEach(function (c) {
      var el = $(c[0]); el.setAttribute('cx', c[1]); el.setAttribute('cy', c[2]); el.setAttribute('r', dot);
    });
    svg.dataset.ratio = vb[3] ? vb[2] / vb[3] : 1;
  }
  /** Ukuran gambar rute: setinggi baris BIB + nama, lebar dibatasi supaya nama tetap lega. */
  function fitRoute(k) {
    var svg = $('x-route');
    if (!svg || svg.hidden) return;
    var ratio = parseFloat(svg.dataset.ratio) || 1, h = 128 * k, w = Math.min(340 * k, h * ratio);
    if (w < h * ratio) h = w / ratio;
    svg.style.width = Math.round(w) + 'px'; svg.style.height = Math.round(h) + 'px';
  }

  /** Isi panel hasil. @returns {boolean} false bila peserta belum punya hasil. */
  function renderResult(r) {
    var x = r.result, fin = !!(x && x.status === 'finisher' && x.gun);
    $('x-fin').hidden = !fin;
    $('x-none').hidden = fin;
    drawRoute(((roster.categories || {})[r.category_id] || {}).route);

    if (fin) {
      $('x-gun').textContent = x.gun;
    } else if (x) {
      $('x-msg').textContent = x.status === 'dq' ? 'Disqualified' : 'Did not finish';
      $('x-sub').textContent = 'Info lebih lanjut di meja informasi panitia';
    } else {
      $('x-msg').textContent = 'Hasil belum tersedia';
      $('x-sub').textContent = roster.published
        ? 'Tidak ada catatan waktu — hubungi meja informasi panitia'
        : 'Hasil muncul setelah kamu melewati garis finish · coba lagi beberapa menit lagi';
    }
    return !!x;
  }

  function showBad(title, code, sub, warn) {
    play(warn ? 'warn' : 'bad');
    $('v-bad').classList.toggle('warn', !!warn);
    $('b-title').textContent = title;
    $('b-code').textContent = code || '';
    $('b-sub').textContent = sub;
    show('v-bad', SHOW_BAD_MS);
  }

  function handle(raw) {
    var text = String(raw || '').trim();
    if (!text) return;
    if (/^GTR\s*\d+$/i.test(text)) return showBad('Ini QR e-ticket', '', 'Untuk cek BIB, scan QR yang tercetak di BIB', true);
    var bib = normalizeBib(text);
    if (!bib) return showBad('Kode tidak dikenal', text.slice(0, 40), 'Scan QR yang tercetak di BIB peserta', true);
    if (!roster) return showBad('Data belum siap', bib, 'Data peserta belum termuat — periksa koneksi internet', true);
    var r = roster.runners && roster.runners[bib];
    if (r) return showRunner(r);
    showBad('BIB tidak terdaftar', 'BIB ' + bib, 'Silakan hubungi meja informasi panitia');
  }

  // ---------------------------------------------------------------- suara ----
  // Mesin scanner berbunyi "bip" untuk SEMUA QR yang terbaca — suara ini membedakan hasilnya:
  // ding naik = BIB terdaftar · buzz 3× = BIB tidak terdaftar · nada tunggal = kode lain.
  // Browser baru mengizinkan suara setelah ada ketukan / ketikan (ketikan mesin scanner pun cukup).
  var SOUND_KEY = 'gtr-bibcheck-sound';
  var soundOn = (function () { try { return localStorage.getItem(SOUND_KEY) !== 'off'; } catch (e) { return true; } })();
  var actx = null;
  function unlockAudio() {
    try {
      if (!actx) { var AC = window.AudioContext || window.webkitAudioContext; if (AC) actx = new AC(); }
      if (actx && actx.state === 'suspended') actx.resume();
    } catch (e) { actx = null; }
  }
  var SOUNDS = {
    ok:   [[880, 0, .12, 'sine', .3], [1320, .11, .22, 'sine', .3]],
    bad:  [[165, 0, .17, 'square', .16], [165, .23, .17, 'square', .16], [165, .46, .32, 'square', .16]],
    warn: [[440, 0, .32, 'triangle', .3]]
  };
  function play(kind) {
    if (!soundOn) return;
    unlockAudio();
    if (!actx) return;
    var t0 = actx.currentTime + .01;
    (SOUNDS[kind] || []).forEach(function (n) {
      var o = actx.createOscillator(), g = actx.createGain();
      o.type = n[3]; o.frequency.value = n[0];
      g.gain.setValueAtTime(.0001, t0 + n[1]);
      g.gain.exponentialRampToValueAtTime(n[4], t0 + n[1] + .012);
      g.gain.exponentialRampToValueAtTime(.0001, t0 + n[1] + n[2]);
      o.connect(g); g.connect(actx.destination);
      o.start(t0 + n[1]); o.stop(t0 + n[1] + n[2] + .03);
    });
  }
  function renderSound() { $('snd').textContent = 'Suara: ' + (soundOn ? 'ON' : 'OFF'); $('snd').classList.toggle('off', !soundOn); }
  $('snd').addEventListener('click', function () {
    soundOn = !soundOn;
    try { localStorage.setItem(SOUND_KEY, soundOn ? 'on' : 'off'); } catch (e) {}
    renderSound(); if (soundOn) play('ok');
    this.blur();
  });
  window.addEventListener('pointerdown', unlockAudio, true);
  window.addEventListener('keydown', unlockAudio, true);

  // ------------------------------------------------------- fokus jendela ----
  // Ketikan mesin scanner hanya masuk ke jendela yang fokus. Bila fokus pindah (klik address bar,
  // aplikasi lain, notifikasi), tampilkan overlay besar sampai layar diketuk lagi.
  // Mode kamera tidak butuh fokus → overlay tidak dipasang selama kamera menyala.
  function checkFocus() { $('lock').hidden = document.hasFocus() || !!(cam && cam.stream); }
  window.addEventListener('blur', checkFocus);
  window.addEventListener('focus', checkFocus);
  document.addEventListener('visibilitychange', checkFocus);
  setInterval(checkFocus, 1000);
  $('lock').addEventListener('pointerdown', function () {
    window.focus(); unlockAudio();
    setTimeout(checkFocus, 60);
  });

  // ------------------------------------------- input mesin scanner (HID) ----
  // Mesin scanner = keyboard yang mengetik sangat cepat lalu Enter. Ketikan ditangkap di
  // seluruh halaman (tanpa kolom input yang harus difokus). Bila mesin tidak mengirim Enter,
  // ketikan cepat dianggap selesai setelah jeda singkat.
  var buf = '', lastAt = 0, fast = true, flushTimer, clearTimer;
  function renderBuf() { $('typed').textContent = buf; }
  function commit() {
    clearTimeout(flushTimer); clearTimeout(clearTimer);
    var v = buf; buf = ''; renderBuf(); handle(v);
  }
  window.addEventListener('keydown', function (e) {
    if (e.ctrlKey || e.metaKey || e.altKey) return;
    if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); if (buf) commit(); return; }
    if (e.key === 'Escape') { buf = ''; renderBuf(); show('v-idle'); return; }
    if (e.key === 'Backspace') { buf = buf.slice(0, -1); renderBuf(); return; }
    if (e.key.length !== 1) return;
    e.preventDefault();

    var now = performance.now();
    if (!buf) fast = true; else if (now - lastAt > SCAN_GAP_MS) fast = false;
    lastAt = now;
    if (buf.length < 64) buf += e.key;
    renderBuf();

    clearTimeout(flushTimer); clearTimeout(clearTimer);
    flushTimer = setTimeout(function () { if (fast && buf.length >= 3) commit(); }, 250);
    clearTimer = setTimeout(function () { buf = ''; renderBuf(); }, 15000); // ketikan manual terbengkalai
  });

  // ------------------------------------------------------------ kamera ----
  // Opsi tanpa mesin scanner: webcam bawaan / kamera eksternal (USB) membaca QR di BIB.
  // Status ON/OFF & pilihan kamera diingat di browser ini → kiosk yang di-reload langsung menyala lagi.
  // Kode yang sama diabaikan selama masih terlihat di kamera (+ CAM_REPEAT_MS setelah hilang),
  // supaya BIB yang terus dipegang di depan kamera tidak berbunyi berulang-ulang.
  var CAM_KEY = 'gtr-bibcheck-cam', CAM_ID_KEY = 'gtr-bibcheck-cam-id';
  var CAM_REPEAT_MS = 4000, CAM_EVERY_MS = 120, CAM_RETRY_MS = 3000, CAM_CROP = 0.75;
  var BUILTIN_CAM = /facetime|built-?in|integrated|internal/i;
  var cam = { want: false, stream: null, starting: false, engine: null, canvas: null, timer: 0, retry: 0, last: '', lastAt: 0 };
  var media = navigator.mediaDevices;

  function getPref(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
  function setPref(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }
  function camMsg(t) { $('cam-msg').textContent = t || ''; }
  function stopTracks(s) { if (s) s.getTracks().forEach(function (t) { t.stop(); }); }

  function renderCam() {
    document.body.classList.toggle('cam-on', cam.want);
    $('cam').textContent = 'Kamera: ' + (cam.want ? 'ON' : 'OFF');
    $('cam-sel').hidden = !cam.want || $('cam-sel').options.length < 2;
    var hint = $('idle-hint');
    if (!hint.dataset.hid) hint.dataset.hid = hint.textContent;
    hint.textContent = cam.want ? hint.dataset.cam : hint.dataset.hid;
    checkFocus();
  }

  function camErr(e) {
    var n = e && e.name;
    if (n === 'NotAllowedError' || n === 'SecurityError') return 'Akses kamera ditolak — izinkan kamera di pengaturan browser';
    if (n === 'NotFoundError' || n === 'OverconstrainedError') return 'Kamera tidak ditemukan — cek kabel USB';
    if (n === 'NotReadableError' || n === 'AbortError') return 'Kamera sedang dipakai aplikasi lain';
    return 'Kamera gagal menyala';
  }

  function camStart() {
    if (!cam.want || cam.stream || cam.starting) return;
    clearTimeout(cam.retry);
    if (!media || !media.getUserMedia) return camMsg(window.isSecureContext ? 'Browser ini tidak mendukung kamera' : 'Kamera butuh HTTPS atau localhost');
    if (!window.QrScanner) return camMsg('Modul scan kamera gagal dimuat');
    cam.starting = true;
    camMsg('Menyalakan kamera…');
    var saved = getPref(CAM_ID_KEY);
    var open = function (id) {
      // 1080p: QR yang dipegang agak jauh (di jarak fokus tajam webcam) tetap cukup besar dalam piksel.
      var v = { width: { ideal: 1920 }, height: { ideal: 1080 } };
      if (id) v.deviceId = { exact: id };
      return media.getUserMedia({ audio: false, video: v });
    };
    open(saved).catch(function (e) {
      // Kamera pilihan dicabut / pindah port → pakai kamera mana pun yang ada.
      if (saved && (e.name === 'NotFoundError' || e.name === 'OverconstrainedError')) return open(null);
      throw e;
    }).then(function (stream) {
      cam.starting = false;
      if (!cam.want) return stopTracks(stream);
      cam.stream = stream;
      var track = stream.getVideoTracks()[0];
      track.addEventListener('ended', function () { if (cam.stream === stream) camLost('Kamera terputus — menyambung ulang…'); });
      var v = $('cam-video');
      v.srcObject = stream;
      v.play().catch(function () {});
      camMsg('');
      checkFocus();
      listCams(track.getSettings ? track.getSettings().deviceId : '');
      (cam.engine ? Promise.resolve(cam.engine) : QrScanner.createQrEngine())
        .then(function (eng) { cam.engine = eng; camLoop(); }, function () { camMsg('Modul scan kamera gagal dimuat'); });
    }, function (e) {
      cam.starting = false;
      camMsg(camErr(e));
      // Izin ditolak tidak akan berubah sendiri; selain itu (kamera dicabut / dipakai) coba lagi.
      if (!e || (e.name !== 'NotAllowedError' && e.name !== 'SecurityError')) cam.retry = setTimeout(camStart, CAM_RETRY_MS);
    });
  }

  function camHalt() {
    clearTimeout(cam.timer); clearTimeout(cam.retry);
    stopTracks(cam.stream); cam.stream = null;
    $('cam-video').srcObject = null;
  }
  function camLost(msg) {
    camHalt();
    if (!cam.want) return;
    camMsg(msg); checkFocus();
    cam.retry = setTimeout(camStart, CAM_RETRY_MS);
  }
  function camRestart() { camHalt(); camStart(); }
  function camStop() {
    cam.want = false; setPref(CAM_KEY, 'off');
    camHalt(); camMsg(''); renderCam();
  }

  /** Isi pilihan kamera (label baru terbaca setelah izin kamera diberikan). */
  function listCams(currentId) {
    if (!media || !media.enumerateDevices) return;
    media.enumerateDevices().then(function (all) {
      var cams = all.filter(function (d) { return d.kind === 'videoinput'; });
      var sel = $('cam-sel');
      sel.innerHTML = '';
      cams.forEach(function (d, i) {
        var o = document.createElement('option');
        o.value = d.deviceId;
        o.textContent = (d.label || 'Kamera ' + (i + 1)).replace(/\s*\([0-9a-f]{4}:[0-9a-f]{4}\)\s*$/i, '');
        sel.appendChild(o);
      });
      if (currentId) sel.value = currentId;
      renderCam();
      // Belum pernah memilih, sedang memakai webcam bawaan, dan ada kamera eksternal → pindah ke eksternal.
      if (getPref(CAM_ID_KEY) || !currentId) return;
      var cur = cams.filter(function (d) { return d.deviceId === currentId; })[0];
      var ext = cams.filter(function (d) { return d.label && !BUILTIN_CAM.test(d.label); })[0];
      if (cur && BUILTIN_CAM.test(cur.label) && ext) { setPref(CAM_ID_KEY, ext.deviceId); camRestart(); }
    }).catch(function () {});
  }

  function camNext(ms) { clearTimeout(cam.timer); cam.timer = setTimeout(camLoop, ms); }
  function camLoop() {
    if (!cam.stream || !cam.engine) return;
    var v = $('cam-video'), w = v.videoWidth, h = v.videoHeight;
    if (document.visibilityState !== 'visible' || v.readyState < 2 || !w) return camNext(300);
    if (v.paused) v.play().catch(function () {});
    // 2 dari 3 frame: kotak tengah (≈ bingkai di pratinjau) pada resolusi asli → QR kecil / jauh tetap
    // terbaca, jadi peserta tak perlu menempelkan BIB terlalu dekat (di bawah jarak fokus webcam).
    // 1 dari 3 frame: seluruh gambar diperkecil → QR besar / di pinggir tetap tertangkap.
    var region;
    cam.tick = (cam.tick || 0) + 1;
    if (cam.tick % 3) {
      var side = Math.round(Math.min(w, h) * CAM_CROP);
      region = { x: Math.round((w - side) / 2), y: Math.round((h - side) / 2), width: side, height: side, downScaledWidth: side, downScaledHeight: side };
    } else {
      var k = Math.min(1, 960 / Math.max(w, h));
      region = { x: 0, y: 0, width: w, height: h, downScaledWidth: Math.round(w * k), downScaledHeight: Math.round(h * k) };
    }
    QrScanner.scanImage(v, {
      returnDetailedScanResult: true,
      qrEngine: cam.engine,
      canvas: cam.canvas || (cam.canvas = document.createElement('canvas')),
      scanRegion: region
    }).then(function (res) { camHit(res && res.data); }, function () { /* tidak ada QR di frame ini */ })
      .then(function () { if (cam.stream) camNext(CAM_EVERY_MS); });
  }

  function camHit(text) {
    text = String(text || '').trim();
    if (!text) return;
    var now = Date.now(), same = text === cam.last && now - cam.lastAt < CAM_REPEAT_MS;
    cam.last = text; cam.lastAt = now;
    if (same) return;
    var box = $('camview');
    box.classList.add('hit');
    setTimeout(function () { box.classList.remove('hit'); }, 450);
    handle(text);
  }

  $('cam').addEventListener('click', function () {
    if (cam.want) camStop();
    else { cam.want = true; setPref(CAM_KEY, 'on'); renderCam(); show('v-idle'); camStart(); }
    this.blur();
  });
  $('cam-sel').addEventListener('change', function () {
    setPref(CAM_ID_KEY, this.value);
    camRestart();
    this.blur();
  });
  // Kamera USB dicolok / dicabut → perbarui daftar, atau nyalakan bila tadi belum ada kamera.
  if (media && media.addEventListener) media.addEventListener('devicechange', function () {
    if (!cam.want) return;
    if (cam.stream) listCams(cam.stream.getVideoTracks()[0].getSettings().deviceId);
    else { clearTimeout(cam.retry); camStart(); }
  });

  // ------------------------------------------------------- kiosk helpers ----
  function tick() { $('clock').textContent = hhmm(Date.now()) + ' WITA'; }
  function keepAwake() {
    if (document.visibilityState !== 'visible' || !navigator.wakeLock) return;
    navigator.wakeLock.request('screen').catch(function () {});
  }
  $('fs').addEventListener('click', function () {
    var el = document.documentElement;
    (el.requestFullscreen ? el.requestFullscreen() : Promise.reject()).catch(function () {});
    keepAwake();
    this.blur();
  });
  document.addEventListener('visibilitychange', keepAwake);

  tick(); setInterval(tick, 1000);
  keepAwake();
  renderSound();
  cam.want = getPref(CAM_KEY) === 'on';
  renderCam();
  if (cam.want) camStart();
  // Offline penuh: halaman, data, font, & logo disimpan service worker (butuh HTTPS / localhost).
  if ('serviceWorker' in navigator) navigator.serviceWorker.register(SW_URL, { scope: SW_SCOPE }).catch(function () {});
  load().then(function () {
    var q = new URLSearchParams(location.search).get('bib');
    if (q) handle(q);
  });
  setInterval(load, REFRESH_MS);
@endverbatim
})();
</script>
</body>
</html>
