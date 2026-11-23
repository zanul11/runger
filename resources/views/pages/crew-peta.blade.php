<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#e9eae5">
<meta name="color-scheme" content="light">
<meta name="robots" content="noindex, nofollow">
<title>Peta Sign &amp; Marshal — GTR Crew</title>
<meta name="description" content="Peta panitia Gerung Trail Run 2026: titik pasang sign arah dan posisi marshal di rute.">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/gtr-scanner-192.png">
<link rel="apple-touch-icon" href="/pwa/gtr-scanner-apple-touch.png">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
{{--
  Halaman publik BACA-SAJA (tanpa login) untuk panitia lapangan, bertema GTR Crew ("lime & ink",
  sama dengan resources/js/scanner/scanner.css). Sign & marshal diatur di Filament → Kategori GTR →
  "Sign & Marshal (khusus panitia)". KM mengikuti profil elevasi (sama dengan Course Map peserta).
--}}
<style>
  :root{
    --bg:#e9eae5;--surface:#fff;--surface-2:#f5f5f1;--surface-3:#e1e2dc;--line:#d7d8d1;--line-strong:#aeb0a6;
    --text:#111;--muted:#46483f;--faint:#6b6d64;--ink:#141414;--lime:#cdee4b;--lime-soft:#eef8c9;--lime-text:#3d5000;
    --gps:#1a73e8;--r:20px;--r-lg:28px;--pad:12px;color-scheme:light;
  }
  *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
  html,body{margin:0;background:var(--bg);color:var(--text);font:600 16px/1.35 system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",sans-serif;-webkit-font-smoothing:antialiased}
  button{font:inherit;color:inherit;cursor:pointer;border:0;background:none;padding:0}
  a{color:inherit}
  .num{font-variant-numeric:tabular-nums}
  [hidden]{display:none !important}
  .icon{width:18px;height:18px;flex:none}
  .shell{max-width:720px;margin:0 auto;padding:max(10px,env(safe-area-inset-top)) var(--pad) calc(28px + env(safe-area-inset-bottom))}

  .top{display:flex;align-items:center;gap:10px;padding-bottom:8px}
  .logo-tile{flex:none;display:grid;place-items:center;width:62px;height:48px;padding:4px 6px;border-radius:14px;background:#fff}
  .logo-tile img{display:block;width:100%;height:100%;object-fit:contain}
  .brand{flex:1;min-width:0;line-height:1.1}
  .brand b{display:block;font-size:17px;font-weight:800;letter-spacing:-.015em}
  .brand small{display:block;color:var(--faint);font-size:12px;font-weight:650}
  .pill{flex:none;display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 14px;border-radius:999px;background:var(--surface);font-size:13px;font-weight:750;text-decoration:none}

  .seg{display:flex;gap:4px;padding:4px;border-radius:999px;background:var(--surface-3);margin-bottom:10px}
  .seg button{flex:1;min-height:42px;border-radius:999px;font-size:15px;font-weight:800;color:var(--muted)}
  .seg button[aria-selected="true"]{background:var(--ink);color:#fff}

  .sum{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .stat{padding:12px 14px;border-radius:var(--r);background:var(--surface)}
  .stat.dark{background:var(--ink);color:#fff}
  .stat small{display:block;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;opacity:.7}
  .stat b{display:block;font-size:26px;font-weight:800;line-height:1.1;margin-top:2px}
  .stat .bd{display:flex;flex-wrap:wrap;gap:4px 10px;margin-top:6px;font-size:12.5px;font-weight:700}
  .stat .bd span{display:inline-flex;align-items:center;gap:5px}
  .dot{display:inline-block;width:11px;height:11px;border-radius:3px;background:var(--c);box-shadow:0 0 0 1.5px #fff}

  .chips{display:flex;gap:6px;margin:10px calc(-1 * var(--pad)) 0;padding:0 var(--pad) 2px;overflow-x:auto;scrollbar-width:none}
  .chips::-webkit-scrollbar{display:none}
  .chip{flex:none;display:inline-flex;align-items:center;gap:6px;min-height:40px;padding:0 16px;border-radius:999px;background:var(--surface);color:var(--muted);font-size:14px;font-weight:750;white-space:nowrap}
  .chip[aria-pressed="true"]{background:var(--lime);color:var(--ink)}

  .mapcard{position:relative;margin-top:10px;border-radius:var(--r-lg);overflow:hidden;background:var(--surface-3)}
  #map{height:min(62vh,560px);min-height:340px}
  .mtools{position:absolute;z-index:500;right:10px;top:10px;display:flex;flex-direction:column;gap:6px}
  .mbtn{display:grid;place-items:center;width:44px;height:44px;border-radius:14px;background:#fff;color:var(--ink);box-shadow:0 2px 8px rgba(0,0,0,.2)}
  .mbtn.on{background:var(--gps);color:#fff}
  .layers{position:absolute;z-index:500;left:10px;top:10px;display:flex;gap:4px;padding:4px;border-radius:999px;background:rgba(255,255,255,.95);box-shadow:0 2px 8px rgba(0,0,0,.2)}
  .layers button{padding:6px 11px;border-radius:999px;font-size:12.5px;font-weight:800;color:var(--muted)}
  .layers button.on{background:var(--ink);color:#fff}
  .gpsbar{display:flex;align-items:flex-start;gap:10px;margin-top:8px;padding:12px 14px;border-radius:var(--r);background:var(--ink);color:#fff;font-size:14px}
  .gpsbar .icon{margin-top:1px;color:var(--lime)}
  .gpsbar small{display:block;margin-top:2px;color:#c9cbc2;font-size:12.5px;font-weight:650}

  .section-title{display:flex;justify-content:space-between;margin:16px 6px 8px;font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint)}
  .list{display:grid;gap:6px}
  .row{display:flex;align-items:center;gap:12px;width:100%;padding:10px 12px 10px 10px;border-radius:var(--r);background:var(--surface);text-align:left}
  .row:active,.row.sel{background:var(--lime-soft)}
  .row.near{box-shadow:inset 0 0 0 2px var(--gps)}
  .kmb{flex:none;display:grid;place-items:center;min-width:58px;height:46px;padding:0 6px;border-radius:14px;background:var(--surface-2);line-height:1}
  .kmb small{font-size:10px;font-weight:800;letter-spacing:.08em;color:var(--faint)}
  .kmb b{font-size:17px;font-weight:800}
  .row .grow{flex:1;min-width:0;display:grid;gap:4px}
  .tags{display:flex;flex-wrap:wrap;gap:5px}
  .tag{display:inline-flex;align-items:center;gap:6px;padding:4px 9px 4px 7px;border-radius:999px;font-size:13px;font-weight:800;background:var(--surface-2)}
  .tag.sign{background:color-mix(in srgb,var(--c) 14%,#fff);color:color-mix(in srgb,var(--c) 75%,#000)}
  .tag.mar{background:var(--ink);color:#fff}
  .tag.mar .icon{width:14px;height:14px;color:var(--lime)}
  .tag.stop{background:#fff;box-shadow:inset 0 0 0 1.5px var(--line);color:var(--muted)}
  .row .note{font-size:12.5px;font-weight:650;color:var(--faint);overflow-wrap:anywhere}
  .go{flex:none;display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:var(--surface-2);color:var(--ink)}
  .empty{padding:24px 16px;border-radius:var(--r);background:var(--surface);text-align:center;color:var(--faint);font-size:14px}
  .foot{margin:18px 6px 0;color:var(--faint);font-size:12px;font-weight:650;line-height:1.5}

  /* ---- Marker Leaflet ---- */
  .mk{background:none;border:0}
  .sgn{position:absolute;left:0;top:0;transform:translate(-50%,-50%);display:grid;place-items:center;width:24px;height:24px;border-radius:7px;background:var(--c);color:#fff;border:2.5px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.45)}
  .sgn svg{width:13px;height:13px}
  .mrs{position:absolute;left:0;top:0;transform:translate(-50%,-100%);display:flex;flex-direction:column;align-items:center}
  .mrs.shift{transform:translate(-50%,-100%) translate(0,-14px)}
  .mrs .hd{display:flex;align-items:center;gap:4px;height:28px;padding:0 9px 0 6px;border-radius:999px;background:var(--ink);color:#fff;font-size:12px;font-weight:800;white-space:nowrap;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.4)}
  .mrs .hd svg{width:15px;height:15px;color:var(--lime)}
  .mrs .st{width:2.5px;height:9px;background:var(--ink)}
  .mrs em{font-style:normal}
  .compact .mrs em{display:none}
  .compact .mrs .hd{padding:0 6px}
  .compact .kmm{display:none}
  .kmm{position:absolute;transform:translate(-50%,-50%);min-width:20px;height:20px;padding:0 4px;border-radius:999px;background:#fff;color:#111;border:1.5px solid #111;font-size:10.5px;font-weight:800;display:grid;place-items:center}
  .stp{position:absolute;transform:translate(-50%,-50%);padding:2px 6px;border-radius:6px;background:#fff;color:#333;font-size:10.5px;font-weight:800;border:1.5px solid var(--c)}
  .sf{position:absolute;transform:translate(-50%,-50%);white-space:nowrap;padding:3px 8px;border-radius:999px;background:var(--c);color:#fff;font-size:11px;font-weight:800;border:2px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.4)}
  .me{position:absolute;transform:translate(-50%,-50%);width:18px;height:18px;border-radius:50%;background:var(--gps);border:3px solid #fff;box-shadow:0 0 0 6px rgba(26,115,232,.25)}
  .sel-ring .sgn,.sel-ring .mrs .hd{outline:3px solid var(--lime);outline-offset:1px}
  .leaflet-popup-content-wrapper{border-radius:16px}
  .leaflet-popup-content{margin:12px 14px;font:600 14px/1.35 system-ui,-apple-system,sans-serif;min-width:180px}
  .pp b{display:block;font-size:15px;font-weight:800}
  .pp small{display:block;color:var(--faint);font-size:12px;margin-top:2px}
  .pp .n{margin-top:6px;font-size:13px;color:var(--muted)}
  .pp a{display:inline-flex;margin-top:8px;padding:7px 12px;border-radius:999px;background:var(--ink);color:#fff !important;text-decoration:none;font-size:12.5px;font-weight:800}
  @media (min-width:720px){ #map{height:560px} }
</style>
</head>
<body>
<div class="shell">
  <header class="top">
    <span class="logo-tile"><img src="{{ asset('gtr/logo-gtr2.png') }}" alt="Gerung Trail Run — Keteri Hill"></span>
    <div class="brand"><b>Peta Sign &amp; Marshal</b><small>GTR Crew · baca-saja</small></div>
    <a class="pill" href="/crew/app">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>GTR Crew
    </a>
  </header>

  @if(empty($courses))
    <div class="empty">Belum ada rute (GPX) kategori yang aktif.</div>
  @else
    <div class="seg" role="tablist" id="cats" @if(count($courses) < 2) hidden @endif>
      @foreach($courses as $c)
        <button role="tab" data-cat="{{ $c['id'] }}" aria-selected="{{ $c['id'] === $defaultId ? 'true' : 'false' }}">{{ $c['name'] }}</button>
      @endforeach
    </div>

    <div class="sum">
      <div class="stat dark"><small>Sign arah</small><b class="num" id="n-sign">0</b><div class="bd" id="bd-sign"></div></div>
      <div class="stat"><small>Marshal</small><b class="num" id="n-mar">0</b><div class="bd" id="bd-mar"></div></div>
    </div>

    <div class="chips" id="filters">
      <button class="chip" data-f="all" aria-pressed="true">Semua</button>
      <button class="chip" data-f="sign" aria-pressed="false">Sign</button>
      @foreach($signColors as $key => $sc)
        <button class="chip" data-f="sign:{{ $key }}" data-color="{{ $key }}" aria-pressed="false"><i class="dot" style="--c:{{ $sc['hex'] }}"></i>{{ $sc['label'] }}</button>
      @endforeach
      <button class="chip" data-f="marshal" aria-pressed="false">Marshal</button>
    </div>

    <div class="mapcard">
      <div id="map" aria-label="Peta rute dengan titik sign dan marshal"></div>
      <div class="layers" id="layers"></div>
      <div class="mtools">
        <button class="mbtn" id="btn-gps" aria-label="Lokasi saya" title="Lokasi saya">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="7.5"/></svg>
        </button>
        <button class="mbtn" id="btn-fit" aria-label="Lihat seluruh rute" title="Seluruh rute">
          <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
        </button>
      </div>
    </div>
    <div class="gpsbar" id="gpsbar" hidden>
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
      <div><span id="gps-main">Mencari lokasi…</span><small id="gps-sub"></small></div>
    </div>

    <div class="section-title"><span>Titik di rute</span><span id="list-count" class="num"></span></div>
    <div class="list" id="list"></div>
    <p class="foot">KM sesuai profil rute resmi (GPX). Ketuk titik untuk melihat di peta, tombol panah membuka Google Maps untuk navigasi ke titik tersebut. Data diatur admin di panel Kategori GTR.</p>
  @endif
</div>

@if(!empty($courses))
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
  window.CREW_MAP = {
    courses: @json($courses),
    defaultId: @json($defaultId),
    colors: @json($signColors),
  };
</script>
@verbatim
<script>
(function(){
  const D = window.CREW_MAP;
  if(!window.L || !D.courses.length) return;
  const $ = id => document.getElementById(id);
  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const kmTxt = v => String(Math.round(v * 100) / 100).replace('.', ',');
  // panah sesuai arah sign (relatif arah lari); arah kosong = tanda tanya
  const arrowSvg = s => !s.direction ? '<b style="font-size:13px;line-height:1">?</b>'
    : s.rotate === null
      ? '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>'
      : '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" style="transform:rotate(' + s.rotate + 'deg)"><path d="M12 20V4M5 11l7-7 7 7"/></svg>';
  const signTxt = s => (s.direction_label ? s.direction_label + ' · ' : 'Sign ') + s.color_label;
  const ICON_MAR = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3 4 6v15h16V6l-4-3"/><path d="M8 3c0 2 1.8 4 4 4s4-2 4-4"/><path d="M4 13h16"/></svg>';
  const ICON_GO = '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z"/></svg>';
  const STOP_COLOR = {ws:'#2EA6E6', cp:'#F97316', cop:'#DC2626'};
  const gmaps = ll => 'https://www.google.com/maps/dir/?api=1&destination=' + ll[0].toFixed(6) + ',' + ll[1].toFixed(6);

  // ---- Peta & basemap ----
  const map = L.map('map', {zoomControl:false, attributionControl:false, tap:false, zoomSnap:.25});
  // zoom jauh: label marshal cukup ikon (nama tim muncul saat diperbesar)
  map.on('zoomend', () => $('map').classList.toggle('compact', map.getZoom() < 15));
  const layers = [
    ['Satelit', L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom:19})],
    ['Topo', L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {maxZoom:17, maxNativeZoom:17})],
    ['Jalan', L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19})],
  ];
  let base = layers[0][1].addTo(map);
  layers.forEach(([name, layer], i) => {
    const b = document.createElement('button');
    b.type = 'button'; b.textContent = name; if(i === 0) b.className = 'on';
    b.addEventListener('click', () => {
      if(base === layer) return;
      map.removeLayer(base); base = layer.addTo(map); base.bringToBack();
      $('layers').querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
    });
    $('layers').appendChild(b);
  });

  const routeLayer = L.layerGroup().addTo(map);
  const markLayer = L.layerGroup().addTo(map);
  let course = null, geo = null, filter = 'all', items = [], selKey = null, nearKey = null;

  // Geometri rute: jarak kumulatif + konversi KM profil ⇄ titik di garis.
  function buildGeo(c){
    const pts = c.points, toRad = d => d * Math.PI / 180, R = 6371, cum = [0];
    for(let i = 1; i < pts.length; i++){
      const a = pts[i-1], b = pts[i], dLa = toRad(b[0]-a[0]), dLo = toRad(b[1]-a[1]);
      const h = Math.sin(dLa/2)**2 + Math.cos(toRad(a[0]))*Math.cos(toRad(b[0]))*Math.sin(dLo/2)**2;
      cum[i] = cum[i-1] + 2*R*Math.asin(Math.min(1, Math.sqrt(h)));
    }
    const total = cum[cum.length-1] || 1, scale = (c.km_total || total) / total;
    return {
      pts, cum, total, kmMax: c.km_total || total,
      at(km){ // KM profil → [lat,lng]
        const d = Math.max(0, Math.min(total, km / scale));
        let j = 1; while(j < cum.length - 1 && cum[j] < d) j++;
        const seg = cum[j] - cum[j-1], t = seg ? (d - cum[j-1]) / seg : 0;
        return [pts[j-1][0] + (pts[j][0]-pts[j-1][0])*t, pts[j-1][1] + (pts[j][1]-pts[j-1][1])*t];
      },
      nearest(ll){ // titik terdekat di rute → {km (profil), dist (m)}
        const k = Math.cos(toRad(ll[0])), P = p => [(p[1]-ll[1]) * k * 111320, (p[0]-ll[0]) * 110540];
        let best = {d2: Infinity, km: 0};
        for(let i = 1; i < pts.length; i++){
          const a = P(pts[i-1]), b = P(pts[i]), vx = b[0]-a[0], vy = b[1]-a[1], L2 = vx*vx + vy*vy;
          const t = L2 ? Math.max(0, Math.min(1, -(a[0]*vx + a[1]*vy) / L2)) : 0;
          const x = a[0] + vx*t, y = a[1] + vy*t, d2 = x*x + y*y;
          if(d2 < best.d2) best = {d2, km: (cum[i-1] + (cum[i]-cum[i-1]) * t) * scale};
        }
        return {km: best.km, dist: Math.sqrt(best.d2)};
      },
    };
  }

  function divIcon(html){ return L.divIcon({className:'mk', iconSize:null, html}); }

  function drawCourse(){
    routeLayer.clearLayers();
    const pts = geo.pts;
    L.polyline(pts, {color:'#000', weight:12, opacity:.35, lineJoin:'round', lineCap:'round', interactive:false}).addTo(routeLayer);
    L.polyline(pts, {color:'#fff', weight:7.5, opacity:1, lineJoin:'round', lineCap:'round', interactive:false}).addTo(routeLayer);
    L.polyline(pts, {color:course.color, weight:4.5, opacity:1, lineJoin:'round', lineCap:'round', interactive:false}).addTo(routeLayer);
    for(let k = 1; k < geo.kmMax - .3; k++){
      L.marker(geo.at(k), {icon:divIcon('<div class="kmm">' + k + '</div>'), interactive:false, keyboard:false, zIndexOffset:-100}).addTo(routeLayer);
    }
    (course.stops || []).forEach(s => {
      L.marker(geo.at(s.km), {icon:divIcon('<div class="stp" style="--c:' + STOP_COLOR[s.type] + '">' + esc(s.code) + '</div>'), keyboard:false, zIndexOffset:100})
        .bindPopup('<div class="pp"><b>' + esc(s.code) + ' · ' + esc(s.title) + '</b><small>KM ' + kmTxt(s.km) + '</small></div>')
        .addTo(routeLayer);
    });
    const first = pts[0], last = pts[pts.length-1], same = map.distance(first, last) < 150;
    L.marker(first, {icon:divIcon('<div class="sf" style="--c:#16A34A">' + (same ? 'START · FINISH' : 'START') + '</div>'), interactive:false, zIndexOffset:200}).addTo(routeLayer);
    if(!same) L.marker(last, {icon:divIcon('<div class="sf" style="--c:#141414">FINISH</div>'), interactive:false, zIndexOffset:200}).addTo(routeLayer);
  }

  // Satu baris daftar per KM (sign & marshal di KM yang sama digabung).
  function buildItems(){
    const byKm = new Map();
    const row = km => { const key = km.toFixed(2); if(!byKm.has(key)) byKm.set(key, {key, km, signs:[], marshals:[], stops:[]}); return byKm.get(key); };
    course.signs.forEach(s => row(s.km).signs.push(s));
    course.marshals.forEach(m => row(m.km).marshals.push(m));
    (course.stops || []).forEach(s => { const k = s.km.toFixed(2); if(byKm.has(k)) byKm.get(k).stops.push(s); });
    return [...byKm.values()].sort((a, b) => a.km - b.km).map(it => Object.assign(it, {ll: geo.at(it.km)}));
  }

  const visibleSigns = it => it.signs.filter(s => filter === 'all' || filter === 'sign' || filter === 'sign:' + s.color);
  const showMarshals = () => filter === 'all' || filter === 'marshal';

  function popupHtml(it){
    let h = '<div class="pp"><b>KM ' + kmTxt(it.km) + '</b>';
    it.signs.forEach(s => { h += '<small>' + esc(s.code) + ' · ' + esc(signTxt(s)) + (s.note ? ' — ' + esc(s.note) : '') + '</small>'; });
    it.marshals.forEach(m => { h += '<small>' + esc(m.code) + ' · Marshal ' + esc(m.team || '-') + ' · ' + m.count + ' orang' + (m.note ? ' — ' + esc(m.note) : '') + '</small>'; });
    return h + '<a href="' + gmaps(it.ll) + '" target="_blank" rel="noopener">Navigasi Google Maps</a></div>';
  }

  function drawMarkers(){
    markLayer.clearLayers();
    items.forEach(it => {
      it.markers = [];
      const signs = visibleSigns(it), mar = showMarshals() ? it.marshals : [];
      if(!signs.length && !mar.length) return;
      const pop = () => popupHtml(it);
      signs.slice(0, 1).forEach(s => {
        const extra = signs.length > 1 ? ' style="--c:' + s.hex + ';box-shadow:4px 4px 0 -1px ' + signs[1].hex + ',0 1px 5px rgba(0,0,0,.45)"' : ' style="--c:' + s.hex + '"';
        it.markers.push(L.marker(it.ll, {icon:divIcon('<div class="sgn"' + extra + '>' + arrowSvg(s) + '</div>'), zIndexOffset:500, riseOnHover:true}).bindPopup(pop, {offset:[0,-8]}));
      });
      if(mar.length){
        const team = [...new Set(mar.map(m => m.team || 'Marshal'))].join(' + ');
        const n = mar.reduce((t, m) => t + m.count, 0);
        it.markers.push(L.marker(it.ll, {icon:divIcon('<div class="mrs' + (signs.length ? ' shift' : '') + '"><span class="hd">' + ICON_MAR + '<em>' + esc(team) + (n > 1 ? ' ×' + n : '') + '</em></span><i class="st"></i></div>'), zIndexOffset:600, riseOnHover:true}).bindPopup(pop, {offset:[0, signs.length ? -52 : -38]}));
      }
      it.markers.forEach(m => { m.on('click', () => select(it.key, false)); m.addTo(markLayer); });
    });
  }

  function renderSummary(){
    const sc = {}; course.signs.forEach(s => sc[s.color] = (sc[s.color] || 0) + 1);
    $('n-sign').textContent = course.signs.length;
    $('bd-sign').innerHTML = Object.keys(D.colors).filter(k => sc[k]).map(k => '<span><i class="dot" style="--c:' + D.colors[k].hex + '"></i>' + D.colors[k].label + ' ' + sc[k] + '</span>').join('') || '<span>—</span>';
    const tc = {}; let people = 0;
    course.marshals.forEach(m => { const t = m.team || 'Lainnya'; tc[t] = (tc[t] || 0) + m.count; people += m.count; });
    $('n-mar').textContent = course.marshals.length + (course.marshals.length ? ' titik' : '');
    $('bd-mar').innerHTML = Object.keys(tc).length
      ? Object.entries(tc).map(([t, n]) => '<span>' + esc(t) + ' ' + n + '</span>').join('') + '<span style="opacity:.7">· ' + people + ' orang</span>'
      : '<span>—</span>';
    // sembunyikan chip warna yang tidak dipakai kategori ini
    document.querySelectorAll('#filters [data-color]').forEach(b => { b.hidden = !sc[b.dataset.color]; });
  }

  function renderList(){
    const rows = items.filter(it => visibleSigns(it).length || (showMarshals() && it.marshals.length));
    $('list-count').textContent = rows.length + ' titik';
    if(!rows.length){
      $('list').innerHTML = '<div class="empty">' + (course.signs.length || course.marshals.length ? 'Tidak ada titik untuk filter ini.' : 'Belum ada sign / marshal untuk kategori ' + esc(course.name) + '.') + '</div>';
      return;
    }
    $('list').innerHTML = rows.map(it => {
      const tags = visibleSigns(it).map(s => '<span class="tag sign" style="--c:' + s.hex + '"><i class="dot" style="--c:' + s.hex + '"></i>' + (s.symbol ? esc(s.symbol) + ' ' : '') + esc(signTxt(s)) + '</span>')
        .concat(showMarshals() ? it.marshals.map(m => '<span class="tag mar">' + ICON_MAR + 'Marshal ' + esc(m.team || '') + (m.count > 1 ? ' ×' + m.count : '') + '</span>') : [])
        .concat(it.stops.map(s => '<span class="tag stop">' + esc(s.code) + '</span>'));
      const notes = it.signs.map(s => s.note).concat(it.marshals.map(m => m.note)).filter(Boolean);
      return '<div class="row' + (it.key === selKey ? ' sel' : '') + (it.key === nearKey ? ' near' : '') + '" data-key="' + it.key + '" role="button" tabindex="0">'
        + '<span class="kmb"><small>KM</small><b class="num">' + kmTxt(it.km) + '</b></span>'
        + '<span class="grow"><span class="tags">' + tags.join('') + '</span>' + (notes.length ? '<span class="note">' + notes.map(esc).join(' · ') + '</span>' : '') + '</span>'
        + '<a class="go" href="' + gmaps(it.ll) + '" target="_blank" rel="noopener" aria-label="Navigasi ke KM ' + kmTxt(it.km) + ' di Google Maps">' + ICON_GO + '</a>'
        + '</div>';
    }).join('');
  }

  function select(key, fly){
    selKey = key;
    const it = items.find(x => x.key === key);
    document.querySelectorAll('#list .row').forEach(r => r.classList.toggle('sel', r.dataset.key === key));
    if(!it) return;
    if(fly){
      map.flyTo(it.ll, Math.max(map.getZoom(), 16), {duration:.6});
      const m = it.markers[it.markers.length - 1];
      if(m) map.once('moveend', () => m.openPopup());
      $('map').scrollIntoView({behavior:'smooth', block:'center'});
    }
  }

  function fit(){ map.fitBounds(L.polyline(geo.pts).getBounds(), {paddingTopLeft:[24, 64], paddingBottomRight:[64, 24]}); }

  function setCourse(id){
    course = D.courses.find(c => c.id === id) || D.courses[0];
    geo = buildGeo(course);
    items = buildItems();
    selKey = null; nearKey = null;
    document.querySelectorAll('#cats button').forEach(b => b.setAttribute('aria-selected', String(Number(b.dataset.cat) === course.id)));
    drawCourse(); drawMarkers(); renderSummary(); renderList(); fit();
    if(lastPos) updateGps(lastPos);
    try { localStorage.setItem('crewMapCat', String(course.id)); } catch(e){}
  }

  // ---- Lokasi saya: posisi di rute + titik berikutnya ----
  let watch = null, meMarker = null, lastPos = null, centered = false;
  function updateGps(pos){
    lastPos = pos;
    const ll = [pos.coords.latitude, pos.coords.longitude];
    if(!meMarker) meMarker = L.marker(ll, {icon:divIcon('<div class="me"></div>'), interactive:false, zIndexOffset:2000}).addTo(map);
    else meMarker.setLatLng(ll);
    if(!centered){ centered = true; map.setView(ll, Math.max(map.getZoom(), 16)); }
    const n = geo.nearest(ll);
    $('gpsbar').hidden = false;
    if(n.dist > 400){
      $('gps-main').textContent = 'Anda ' + (n.dist >= 1000 ? (n.dist / 1000).toFixed(1).replace('.', ',') + ' km' : Math.round(n.dist) + ' m') + ' dari rute ' + course.name;
      $('gps-sub').textContent = 'Titik rute terdekat ± KM ' + kmTxt(Math.round(n.km * 10) / 10);
    } else {
      $('gps-main').textContent = 'Anda di ± KM ' + kmTxt(Math.round(n.km * 10) / 10) + ' (' + Math.round(n.dist) + ' m dari rute)';
      const next = items.find(it => it.km >= n.km - 0.02 && (visibleSigns(it).length || (showMarshals() && it.marshals.length)));
      $('gps-sub').textContent = next
        ? 'Berikutnya KM ' + kmTxt(next.km) + ': ' + [next.signs.length ? next.signs.map(signTxt).join(' + ') : '', next.marshals.length ? 'Marshal ' + next.marshals.map(m => m.team).join(' + ') : ''].filter(Boolean).join(' · ')
          + ' — ' + Math.max(0, Math.round((next.km - n.km) * 1000)) + ' m lagi'
        : 'Tidak ada titik lagi di depan.';
    }
    const near = items.reduce((b, it) => Math.abs(it.km - n.km) < (b ? Math.abs(b.km - n.km) : Infinity) ? it : b, null);
    const nk = n.dist <= 400 && near && Math.abs(near.km - n.km) <= 0.1 ? near.key : null;
    if(nk !== nearKey){ nearKey = nk; renderList(); }
  }
  $('btn-gps').addEventListener('click', () => {
    if(watch !== null){
      navigator.geolocation.clearWatch(watch); watch = null; lastPos = null; centered = false; nearKey = null;
      if(meMarker){ map.removeLayer(meMarker); meMarker = null; }
      $('btn-gps').classList.remove('on'); $('gpsbar').hidden = true; renderList();
      return;
    }
    if(!navigator.geolocation){ alert('Browser tidak mendukung lokasi.'); return; }
    $('btn-gps').classList.add('on'); $('gpsbar').hidden = false;
    $('gps-main').textContent = 'Mencari lokasi…'; $('gps-sub').textContent = '';
    watch = navigator.geolocation.watchPosition(updateGps, err => {
      $('gps-main').textContent = err.code === 1 ? 'Izin lokasi ditolak.' : 'Lokasi belum didapat.';
      $('gps-sub').textContent = err.code === 1 ? 'Izinkan akses lokasi untuk halaman ini di pengaturan browser.' : 'Pastikan GPS aktif, coba di area terbuka.';
    }, {enableHighAccuracy:true, maximumAge:5000, timeout:20000});
  });
  $('btn-fit').addEventListener('click', fit);

  // ---- Interaksi ----
  document.querySelectorAll('#cats button').forEach(b => b.addEventListener('click', () => setCourse(Number(b.dataset.cat))));
  document.querySelectorAll('#filters .chip').forEach(b => b.addEventListener('click', () => {
    filter = b.dataset.f;
    document.querySelectorAll('#filters .chip').forEach(x => x.setAttribute('aria-pressed', String(x === b)));
    drawMarkers(); renderList();
    if(lastPos) updateGps(lastPos);
  }));
  $('list').addEventListener('click', e => {
    if(e.target.closest('a')) return;
    const r = e.target.closest('.row'); if(r) select(r.dataset.key, true);
  });
  $('list').addEventListener('keydown', e => {
    const r = e.target.closest('.row'); if(r && (e.key === 'Enter' || e.key === ' ')){ e.preventDefault(); select(r.dataset.key, true); }
  });

  let start = D.defaultId;
  try { const s = Number(localStorage.getItem('crewMapCat')); if(D.courses.some(c => c.id === s)) start = s; } catch(e){}
  setCourse(start);
})();
</script>
@endverbatim
@endif
</body>
</html>
