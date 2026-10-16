@extends('layouts.gtr-preview')

@section('title', 'Preview Course Map ' . $cat->distance . ' — GTR Admin')

@push('styles')
<style>
  .pv-wrap{max-width:1320px;margin:0 auto;padding:20px 20px 60px}
  .pv-bar{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
  .pv-title{font-family:'Archivo',sans-serif;font-weight:900;font-size:22px;text-transform:uppercase}
  .pv-title small{display:block;font-size:11px;letter-spacing:.14em;color:#8A8A8A;font-weight:800}
  .pv-cats{display:flex;gap:6px;flex-wrap:wrap}
  .pv-cats a{padding:8px 14px;border-radius:999px;background:#fff;border:1px solid #E2E2E2;font-family:'Archivo',sans-serif;font-weight:800;font-size:12px}
  .pv-cats a.on{background:#101010;color:#fff;border-color:#101010}
  .pv-export{display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end;padding:16px 18px;margin-bottom:16px;border-radius:18px;background:#101010;color:#fff}
  .pv-export label{display:flex;flex-direction:column;gap:6px;font-family:'Archivo',sans-serif;font-weight:800;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.6)}
  .pv-export select{padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#1C1C1C;color:#fff;font:inherit;font-size:13px;letter-spacing:0;text-transform:none}
  .pv-export .chk{flex-direction:row;align-items:center;gap:8px;color:#fff;font-size:12.5px;letter-spacing:0;text-transform:none;font-weight:700;padding-bottom:10px}
  .pv-export .chk input{width:16px;height:16px;accent-color:#E53935}
  .pv-marks{grid-column:1 / -1;display:flex;flex-direction:column;gap:8px;padding-top:4px;border-top:1px solid rgba(255,255,255,.1)}
  .pv-mk-h{font-family:'Archivo',sans-serif;font-weight:800;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.6);padding-top:8px}
  .pv-mk-h span{letter-spacing:0;text-transform:none;font-weight:600;opacity:.7;margin-left:6px}
  .pv-mk-row{display:flex;flex-wrap:wrap;gap:6px}
  .pv-mk-row button{padding:7px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.18);background:transparent;color:rgba(255,255,255,.45);font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;cursor:pointer;text-decoration:line-through}
  .pv-mk-row button.on{background:#fff;color:#101010;border-color:#fff;text-decoration:none}
  .pv-mk-row.items button.on{background:rgba(255,255,255,.12);color:#fff;border-color:var(--c,#fff)}
  .pv-mk-row.items button i{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--c,#fff);margin-right:6px}
  .pv-mk-row button:disabled{opacity:.3;cursor:not-allowed}
  .pv-btn{padding:12px 18px;border-radius:12px;border:none;background:#E53935;color:#fff;font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;letter-spacing:.04em;cursor:pointer}
  .pv-btn:disabled{opacity:.6;cursor:wait}
  .pv-status{grid-column:1 / -1;font-size:12.5px;color:rgba(255,255,255,.7);min-height:18px}
  .pv-result{display:none;margin-bottom:16px;padding:14px;border-radius:18px;background:#fff;border:1px solid #E2E2E2}
  .pv-result.on{display:block}
  .pv-result img{display:block;width:100%;height:auto;border-radius:10px;border:1px solid #EEE}
  .pv-result .row{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;font-size:13px;color:#555}
  .pv-result a{padding:10px 16px;border-radius:10px;background:#101010;color:#fff;font-family:'Archivo',sans-serif;font-weight:800;font-size:12.5px}
  .pv-panel{background:#fff;border-radius:24px;padding:16px 22px 28px;box-shadow:0 18px 50px rgba(0,0,0,.12)}
</style>
@endpush

@section('content')
<div class="pv-wrap">
  <div class="pv-bar">
    <div class="pv-title"><small>Preview Course Map · tampilan sama dengan halaman detail kategori</small>{{ $cat->distance }} — {{ $cat->name }}</div>
    <div class="pv-cats">
      @foreach($categories as $c)
        <a href="{{ route('gtr.course-preview', $c) }}" @class(['on' => $c->id === $cat->id])>{{ $c->distance }}</a>
      @endforeach
      <a href="{{ route('gtr.category', $cat->slug) }}" target="_blank" rel="noopener">Buka halaman publik ↗</a>
    </div>
  </div>

  @if($cat->route_points)
  <div class="pv-export" id="pv-export">
    <label>Ukuran
      <select data-x="size">
        <option value="landscape">Landscape 3600 × 2400</option>
        <option value="portrait">Portrait 2400 × 3600</option>
        <option value="square">Square 3000 × 3000</option>
        <option value="story">Story 2160 × 3840</option>
        <option value="hd">Landscape XL 4800 × 3200</option>
      </select>
    </label>
    <label>Basemap
      <select data-x="base">
        <option value="satellite">Satelit</option>
        <option value="topo">Topo (kontur)</option>
        <option value="light">Light (polos)</option>
      </select>
    </label>
    <label>Gaya
      <select data-x="style">
        <option value="poster">Poster (rute dominan)</option>
        <option value="classic">Klasik (label kotak)</option>
      </select>
    </label>
    <label>Warna rute
      <select data-x="color">
        <option value="route">Warna kategori</option>
        <option value="grade">Gradien tanjakan</option>
      </select>
    </label>
    <label class="chk"><input type="checkbox" data-x="elev" checked> Profil elevasi</label>
    <div class="pv-marks">
      <div class="pv-mk-h">Tampilkan marker <span>klik untuk sembunyikan</span></div>
      <div class="pv-mk-row" data-x="types">
        <button type="button" class="on" data-show="km">Penanda KM</button>
        <button type="button" class="on" data-show="ws">Water Station</button>
        <button type="button" class="on" data-show="cp">Check Point</button>
        <button type="button" class="on" data-show="sf">Start / Finish</button>
        <button type="button" class="on" data-show="summit">Titik tertinggi</button>
        <button type="button" class="on" data-show="pois">Info &amp; bahaya jalur</button>
        <button type="button" class="on" data-show="legend">Legenda</button>
        <button type="button" class="on" data-show="scale">Skala &amp; arah utara</button>
      </div>
      <div class="pv-mk-row items" data-x="items"></div>
    </div>
    <button type="button" class="pv-btn" data-x="go">Export Gambar PNG</button>
    <div class="pv-status" data-x="status">Gambar digambar ulang dalam resolusi tinggi (bukan screenshot) — label otomatis disusun agar tidak saling menutupi.</div>
  </div>
  <div class="pv-result" id="pv-result">
    <div class="row"><span data-x="info"></span><a data-x="dl" download>Download PNG</a></div>
    <img data-x="img" alt="Hasil export course map">
  </div>
  @endif

  <div class="pv-panel">
    @include('pages.partials.gtr-course', ['cat' => $cat])
  </div>
</div>
@endsection

@push('scripts')
<script>window.__course = @json($export);</script>
@verbatim
<script>
(function () {
  const box = document.getElementById('pv-export');
  if (!box) return;
  const D = window.__course;
  if (!/^#[0-9a-f]{6}$/i.test(D.color || '')) D.color = '#E53935';
  const $ = k => document.querySelector('[data-x="' + k + '"]');
  const res = document.getElementById('pv-result');

  const PRESETS = {landscape: [3600, 2400], portrait: [2400, 3600], square: [3000, 3000], story: [2160, 3840], hd: [4800, 3200]};
  const BASES = {
    topo: {max: 17, url: (z, x, y) => 'https://' + 'abc'[Math.abs(x + y) % 3] + '.tile.opentopomap.org/' + z + '/' + x + '/' + y + '.png', attr: 'Peta © OpenTopoMap (CC-BY-SA) · data © kontributor OpenStreetMap'},
    satellite: {max: 18, url: (z, x, y) => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/' + z + '/' + y + '/' + x, attr: 'Citra © Esri, Maxar, Earthstar Geographics'},
    light: {max: 19, url: (z, x, y) => 'https://' + 'abcd'[Math.abs(x + y) % 4] + '.basemaps.cartocdn.com/light_all/' + z + '/' + x + '/' + y + '@2x.png', attr: 'Peta © CARTO · data © kontributor OpenStreetMap'},
  };
  const PT = {ws: '#2EA6E6', cp: '#F97316'};
  const gradeColor = g => g < -3 ? '#2563EB' : g < 3 ? '#22C55E' : g < 8 ? '#FACC15' : g < 15 ? '#F97316' : '#DC2626';
  const FONT = "'Archivo', 'Helvetica Neue', Arial, sans-serif";

  // ---- geometri rute ----
  const R = D.route, rad = d => d * Math.PI / 180;
  const cum = [0];
  for (let i = 1; i < R.length; i++) {
    const [a1, o1] = R[i - 1], [a2, o2] = R[i];
    const h = Math.sin(rad(a2 - a1) / 2) ** 2 + Math.cos(rad(a1)) * Math.cos(rad(a2)) * Math.sin(rad(o2 - o1) / 2) ** 2;
    cum[i] = cum[i - 1] + 2 * 6371 * Math.asin(Math.min(1, Math.sqrt(h)));
  }
  const total = cum[cum.length - 1] || 1;
  const ep = D.ep || [];
  const epMax = ep.length ? Number(ep[ep.length - 1][0]) : total;
  const kmLL = km => {
    const k = Math.max(0, Math.min(total, km / epMax * total));
    let j = 1; while (j < cum.length && cum[j] < k) j++;
    if (j >= cum.length) j = cum.length - 1;
    const s = cum[j] - cum[j - 1], t = s ? (k - cum[j - 1]) / s : 0;
    return [R[j - 1][0] + (R[j][0] - R[j - 1][0]) * t, R[j - 1][1] + (R[j][1] - R[j - 1][1]) * t];
  };
  const eleAt = km => {
    if (!ep.length) return 0;
    for (let i = 1; i < ep.length; i++) if (ep[i][0] >= km) {
      const t = ep[i][0] - ep[i - 1][0] ? (km - ep[i - 1][0]) / (ep[i][0] - ep[i - 1][0]) : 0;
      return ep[i - 1][1] + (ep[i][1] - ep[i - 1][1]) * t;
    }
    return ep[ep.length - 1][1];
  };
  // Web Mercator, koordinat dunia pada zoom 0 (256 px)
  const wx = lng => (lng + 180) / 360 * 256;
  const wy = lat => (1 - Math.log(Math.tan(rad(lat)) + 1 / Math.cos(rad(lat))) / Math.PI) / 2 * 256;

  // ---- utilitas canvas ----
  const rr = (c, x, y, w, h, r) => { c.beginPath(); c.moveTo(x + r, y); c.arcTo(x + w, y, x + w, y + h, r); c.arcTo(x + w, y + h, x, y + h, r); c.arcTo(x, y + h, x, y, r); c.arcTo(x, y, x + w, y, r); c.closePath(); };
  const loadImg = src => new Promise(ok => { const im = new Image(); im.crossOrigin = 'anonymous'; im.onload = () => ok(im); im.onerror = () => ok(null); im.src = src; });
  const hit = (a, b) => a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;
  const fmtKm = v => (Math.round(v * 10) / 10).toString();

  async function render(o) {
    const [W, H] = PRESETS[o.size];
    const u = Math.min(W, H) / 1200;
    const cv = document.createElement('canvas'); cv.width = W; cv.height = H;
    const c = cv.getContext('2d');
    c.textBaseline = 'middle';
    await document.fonts.ready;

    const headH = 170 * u, footH = 56 * u;
    const elevH = o.elev && ep.length ? (W >= H ? 330 * u : 420 * u) : 0;
    const M = {x: 0, y: headH, w: W, h: H - headH - elevH - footH};

    // proyeksi rute ke area peta
    const P = R.map(([la, ln]) => [wx(ln), wy(la)]);
    const xs = P.map(p => p[0]), ys = P.map(p => p[1]);
    const minX = Math.min(...xs), maxX = Math.max(...xs), minY = Math.min(...ys), maxY = Math.max(...ys);
    const pad = 150 * u;
    const S = Math.min((M.w - 2 * pad) / (maxX - minX || 1e-6), (M.h - 2 * pad) / (maxY - minY || 1e-6));
    const cx = (minX + maxX) / 2, cy = (minY + maxY) / 2;
    const pr = (la, ln) => [M.x + M.w / 2 + (wx(ln) - cx) * S, M.y + M.h / 2 + (wy(la) - cy) * S];

    // ---- basemap ----
    const base = BASES[o.base];
    const zt = Math.max(1, Math.min(base.max, Math.ceil(Math.log2(S))));
    const tw = 256 / 2 ** zt, tpx = tw * S, n = 2 ** zt;
    const wl = cx - M.w / 2 / S, wt = cy - M.h / 2 / S;
    const tx0 = Math.floor(wl / tw), tx1 = Math.floor((cx + M.w / 2 / S) / tw);
    const ty0 = Math.max(0, Math.floor(wt / tw)), ty1 = Math.min(n - 1, Math.floor((cy + M.h / 2 / S) / tw));
    const jobs = [];
    for (let x = tx0; x <= tx1; x++) for (let y = ty0; y <= ty1; y++) jobs.push([x, y]);
    c.save(); c.beginPath(); c.rect(M.x, M.y, M.w, M.h); c.clip();
    c.fillStyle = '#E8ECE3'; c.fillRect(M.x, M.y, M.w, M.h);
    let done = 0, failed = 0;
    const queue = jobs.slice();
    async function worker() {
      while (queue.length) {
        const [x, y] = queue.shift();
        const im = await loadImg(base.url(zt, ((x % n) + n) % n, y));
        if (im) c.drawImage(im, M.x + M.w / 2 + (x * tw - cx) * S, M.y + M.h / 2 + (y * tw - cy) * S, tpx + .6, tpx + .6); else failed++;
        done++; status('Memuat peta ' + Math.round(done / jobs.length * 100) + '% (' + done + '/' + jobs.length + ' tile)…');
      }
    }
    await Promise.all(Array.from({length: 8}, worker));
    c.fillStyle = o.base === 'satellite' ? 'rgba(0,0,0,.14)' : 'rgba(255,255,255,.12)';
    c.fillRect(M.x, M.y, M.w, M.h);
    status('Menggambar rute & label…');

    // ---- ruas info/bahaya ----
    const show = Object.assign({km: true, ws: true, cp: true, sf: true, summit: true, pois: true, legend: true, scale: true}, o.show || {});
    const hidden = o.hidden || new Set();
    const pois = show.pois ? (D.pois || []).filter((p, i) => !hidden.has('poi:' + i)) : [];
    const pts = (D.points || []).filter((p, i) => show[p.type] && !hidden.has('pt:' + i));
    c.lineCap = 'round'; c.lineJoin = 'round';
    pois.filter(p => p.km_end).forEach(p => {
      c.beginPath();
      for (let k = p.km; k <= p.km_end; k += .02) { const [x, y] = pr(...kmLL(k)); k === p.km ? c.moveTo(x, y) : c.lineTo(x, y); }
      c.strokeStyle = p.hazard ? 'rgba(249,115,22,.5)' : 'rgba(55,65,81,.4)'; c.lineWidth = 30 * u; c.stroke();
    });

    // ---- rute ----
    const path = () => { c.beginPath(); R.forEach(([la, ln], i) => { const [x, y] = pr(la, ln); i ? c.lineTo(x, y) : c.moveTo(x, y); }); };
    path(); c.strokeStyle = 'rgba(16,16,16,.55)'; c.lineWidth = 17 * u; c.stroke();
    path(); c.strokeStyle = '#ffffff'; c.lineWidth = 12.5 * u; c.stroke();
    if (o.color === 'grade' && ep.length) {
      for (let i = 1; i < R.length; i++) {
        const mid = (cum[i - 1] + cum[i]) / 2 / total * epMax, a = Math.max(0, mid - .1), b = Math.min(epMax, mid + .1);
        const g = b - a > .001 ? (eleAt(b) - eleAt(a)) / ((b - a) * 1000) * 100 : 0;
        const [x1, y1] = pr(...R[i - 1]), [x2, y2] = pr(...R[i]);
        c.beginPath(); c.moveTo(x1, y1); c.lineTo(x2, y2); c.strokeStyle = gradeColor(g); c.lineWidth = 8 * u; c.stroke();
      }
    } else { path(); c.strokeStyle = D.color; c.lineWidth = 7.5 * u; c.stroke(); }

    // ---- label (anti-tumpuk) ----
    const labels = [];
    const addLabel = (km, ll, text, style, prio) => labels.push({km, ll, text, style, prio});
    const first = R[0], last = R[R.length - 1];
    const sameSF = Math.hypot(...[0, 1].map(i => (first[i] - last[i]) * 111000)) < 150;
    if (show.sf) {
      if (sameSF) addLabel(0, first, 'START · FINISH', 'sf', 0);
      else { addLabel(0, first, 'START', 'start', 0); addLabel(epMax, last, 'FINISH', 'sf', 0); }
    }
    pts.forEach(p => addLabel(Number(p.km), kmLL(Number(p.km)), p.code + ' · KM ' + fmtKm(p.km), p.type, 1));
    if (show.summit && ep.length) { let top = ep[0]; ep.forEach(p => { if (p[1] > top[1]) top = p; }); addLabel(top[0], kmLL(top[0]), '▲ ' + Math.round(top[1]) + ' mdpl', 'summit', 2); }
    pois.forEach(p => addLabel(Number(p.km), kmLL(Number(p.km)), p.icon + '  ' + p.title, p.hazard ? 'hz' : 'info', 3));

    const fs = 21 * u, ph = 42 * u, stem = 14 * u;
    c.font = '800 ' + fs + 'px ' + FONT;
    const placed = [];
    const anchors = [];
    labels.sort((a, b) => a.prio - b.prio || a.km - b.km).forEach(L => {
      const [ax, ay] = pr(...L.ll);
      const w = c.measureText(L.text).width + (L.style === 'sf' || L.style === 'start' ? 72 : 36) * u;
      let spot = null;
      // coba posisi bertingkat ke atas, lalu ke bawah, dengan geser kiri/kanan
      const tries = [];
      for (let lv = 0; lv < 8; lv++) [0, 1, -1].forEach(sh => tries.push([sh * (w / 2 + 8 * u), -(stem + ph) - lv * (ph + 10 * u)]));
      for (let lv = 0; lv < 5; lv++) [0, 1, -1].forEach(sh => tries.push([sh * (w / 2 + 8 * u), stem + lv * (ph + 10 * u)]));
      for (const [dx, dy] of tries) {
        const r = {x: ax + dx - w / 2, y: ay + dy, w, h: ph};
        if (r.x < M.x + 10 * u || r.x + r.w > M.x + M.w - 10 * u || r.y < M.y + 10 * u || r.y + r.h > M.y + M.h - 10 * u) continue;
        if (placed.some(p => hit(p, {x: r.x - 6 * u, y: r.y - 4 * u, w: r.w + 12 * u, h: r.h + 8 * u}))) continue;
        spot = r; break;
      }
      if (!spot) spot = {x: ax - w / 2, y: ay - stem - ph, w, h: ph};
      placed.push(spot); anchors.push([ax, ay]);
      L.rect = spot; L.ax = ax; L.ay = ay;
    });

    // ---- badge KM (dilewati bila tertutup label) ----
    const kmStep = (() => { const a = pr(...kmLL(0)), b = pr(...kmLL(Math.min(1, epMax))); return Math.hypot(a[0] - b[0], a[1] - b[1]) < 70 * u ? 2 : 1; })();
    const kr = 17 * u;
    for (let k = kmStep; show.km && k < epMax - .3; k += kmStep) {
      const [x, y] = pr(...kmLL(k));
      const kb = {x: x - kr, y: y - kr, w: 2 * kr, h: 2 * kr};
      if (placed.some(p => hit(p, kb)) || anchors.some(([ax, ay]) => Math.hypot(ax - x, ay - y) < kr * 1.6)) continue;
      c.beginPath(); c.arc(x, y, kr, 0, Math.PI * 2); c.fillStyle = '#fff'; c.fill(); c.lineWidth = 3.5 * u; c.strokeStyle = '#101010'; c.stroke();
      c.fillStyle = '#101010'; c.font = '900 ' + (16 * u) + 'px ' + FONT; c.textAlign = 'center'; c.fillText(String(k), x, y + u);
    }

    // ---- garis penunjuk + titik lokasi asli ----
    labels.forEach(L => {
      const r = L.rect, bx = Math.max(r.x + 12 * u, Math.min(r.x + r.w - 12 * u, L.ax)), by = r.y > L.ay ? r.y : r.y + r.h;
      c.beginPath(); c.moveTo(L.ax, L.ay); c.lineTo(bx, by);
      c.setLineDash(Math.hypot(bx - L.ax, by - L.ay) > stem * 1.5 ? [6 * u, 5 * u] : []);
      c.strokeStyle = '#101010'; c.lineWidth = 3 * u; c.stroke(); c.setLineDash([]);
      c.beginPath(); c.arc(L.ax, L.ay, 7.5 * u, 0, Math.PI * 2); c.fillStyle = '#fff'; c.fill(); c.lineWidth = 4 * u; c.strokeStyle = '#101010'; c.stroke();
    });

    // ---- label ----
    labels.forEach(L => {
      const r = L.rect;
      const st = {
        sf: ['#101010', '#fff', null], start: ['#16A34A', '#fff', null], ws: [PT.ws, '#fff', '#fff'], cp: [PT.cp, '#fff', '#fff'],
        summit: ['#fff', '#101010', '#101010'], hz: ['#FFF7ED', '#9A3412', '#F97316'], info: ['#fff', '#101010', '#374151'],
      }[L.style];
      c.save(); c.shadowColor = 'rgba(0,0,0,.35)'; c.shadowBlur = 14 * u; c.shadowOffsetY = 4 * u;
      rr(c, r.x, r.y, r.w, r.h, 11 * u); c.fillStyle = st[0]; c.fill(); c.restore();
      if (st[2]) { rr(c, r.x, r.y, r.w, r.h, 11 * u); c.lineWidth = 3 * u; c.strokeStyle = st[2]; c.stroke(); }
      let tx = r.x + 18 * u;
      if (L.style === 'sf' || L.style === 'start') { // bendera
        const fx = r.x + 14 * u, fy = r.y + 9 * u, fsz = 24 * u, q = fsz / 4;
        for (let i = 0; i < 4; i++) for (let j = 0; j < 4; j++) { c.fillStyle = (i + j) % 2 ? '#101010' : '#fff'; c.fillRect(fx + i * q, fy + j * q, q, q); }
        tx = fx + fsz + 12 * u;
      }
      c.fillStyle = st[1]; c.font = '800 ' + fs + 'px ' + FONT; c.textAlign = 'left';
      c.fillText(L.text, tx, r.y + r.h / 2 + u);
    });
    c.restore(); // akhir clip peta

    // ---- legenda & skala ----
    const lg = [];
    if (pts.some(p => p.type === 'ws')) lg.push(['ws', 'Water Station']);
    if (pts.some(p => p.type === 'cp')) lg.push(['cp', 'Check Point']);
    if (show.km) lg.push(['km', 'Penanda KM']);
    if (show.sf) lg.push(['sf', 'Start / Finish']);
    if (pois.length) lg.push(['hz', 'Info & bahaya jalur']);
    if (show.legend && (lg.length || o.color === 'grade')) {
    const lx = M.x + 28 * u, lh = 34 * u, lw = 330 * u, lH = 28 * u + lg.length * lh + (o.color === 'grade' ? 74 * u : 0);
    const ly = M.y + M.h - lH - 28 * u;
    c.save(); c.shadowColor = 'rgba(0,0,0,.25)'; c.shadowBlur = 20 * u; rr(c, lx, ly, lw, lH, 16 * u); c.fillStyle = 'rgba(255,255,255,.95)'; c.fill(); c.restore();
    lg.forEach(([k, t], i) => {
      const y = ly + 30 * u + i * lh, x = lx + 22 * u;
      if (k === 'km') { c.beginPath(); c.arc(x + 14 * u, y, 12 * u, 0, 7); c.fillStyle = '#fff'; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = '#101010'; c.stroke(); }
      else { rr(c, x, y - 11 * u, 28 * u, 22 * u, 6 * u); c.fillStyle = k === 'ws' ? PT.ws : k === 'cp' ? PT.cp : k === 'hz' ? '#FFF7ED' : '#101010'; c.fill(); if (k === 'hz') { c.lineWidth = 3 * u; c.strokeStyle = '#F97316'; c.stroke(); } }
      c.fillStyle = '#101010'; c.font = '700 ' + (18 * u) + 'px ' + FONT; c.textAlign = 'left'; c.fillText(t, x + 44 * u, y + u);
    });
    if (o.color === 'grade') {
      const gy = ly + 30 * u + lg.length * lh + 6 * u;
      [['#2563EB', 'Turun'], ['#22C55E', '<3%'], ['#FACC15', '3–8'], ['#F97316', '8–15'], ['#DC2626', '>15%']].forEach(([col, t], i) => {
        const x = lx + 22 * u + i * 58 * u; c.fillStyle = col; rr(c, x, gy, 48 * u, 10 * u, 5 * u); c.fill();
        c.fillStyle = '#444'; c.font = '700 ' + (13 * u) + 'px ' + FONT; c.fillText(t, x, gy + 28 * u);
      });
    }
    }
    // skala
    if (show.scale) {
    const latC = (Math.min(...R.map(p => p[0])) + Math.max(...R.map(p => p[0]))) / 2;
    const mpp = 40075016.686 * Math.cos(rad(latC)) / 256 / S;
    const nice = [100, 200, 250, 500, 1000, 2000, 5000].find(m => m / mpp > 150 * u) || 5000;
    const sw = nice / mpp, sx = M.x + M.w - 28 * u - sw, sy = M.y + M.h - 40 * u;
    rr(c, sx - 16 * u, sy - 38 * u, sw + 32 * u, 60 * u, 12 * u); c.fillStyle = 'rgba(255,255,255,.92)'; c.fill();
    c.fillStyle = '#101010'; c.fillRect(sx, sy, sw, 6 * u); c.fillRect(sx, sy - 8 * u, 3 * u, 14 * u); c.fillRect(sx + sw - 3 * u, sy - 8 * u, 3 * u, 14 * u);
    c.font = '800 ' + (16 * u) + 'px ' + FONT; c.textAlign = 'center'; c.fillText(nice >= 1000 ? nice / 1000 + ' km' : nice + ' m', sx + sw / 2, sy - 18 * u);
    // utara
    const nx = M.x + M.w - 60 * u, ny = M.y + 64 * u;
    c.beginPath(); c.arc(nx, ny, 32 * u, 0, 7); c.fillStyle = 'rgba(255,255,255,.92)'; c.fill();
    c.beginPath(); c.moveTo(nx, ny - 22 * u); c.lineTo(nx + 11 * u, ny + 8 * u); c.lineTo(nx, ny + 2 * u); c.lineTo(nx - 11 * u, ny + 8 * u); c.closePath(); c.fillStyle = '#E53935'; c.fill();
    c.fillStyle = '#101010'; c.font = '900 ' + (13 * u) + 'px ' + FONT; c.fillText('N', nx, ny + 20 * u);
    }

    // ---- header ----
    c.fillStyle = '#101010'; c.fillRect(0, 0, W, headH); c.fillStyle = '#E53935'; c.fillRect(0, headH - 7 * u, W, 7 * u);
    c.textAlign = 'left';
    c.fillStyle = '#FF8A80'; c.font = '800 ' + (19 * u) + 'px ' + FONT; c.fillText(('GERUNG TRAIL RUN 2026 · ' + (D.tag || 'COURSE MAP')).toUpperCase(), 48 * u, 50 * u);
    c.fillStyle = '#fff'; c.font = '900 ' + (78 * u) + 'px ' + FONT; c.fillText(D.distance || '', 46 * u, 112 * u);
    const dw = c.measureText(D.distance || '').width;
    if (D.name && D.name.replace(/\s/g, '').toUpperCase() !== (D.distance || '').replace(/\s/g, '').toUpperCase()) {
      c.fillStyle = '#B4B4B4'; c.font = '700 ' + (26 * u) + 'px ' + FONT; c.fillText(D.name.toUpperCase(), 46 * u + dw + 24 * u, 116 * u);
    }
    const maxEle = ep.length ? Math.round(Math.max(...ep.map(p => p[1]))) : null;
    const stats = [['Jarak', fmtKm(epMax) + ' km'], ['Elevation Gain', D.gain || '-'], ['Titik Tertinggi', maxEle !== null ? maxEle + ' mdpl' : '-'], ['Pos', (D.points || []).filter(p => p.type === 'ws').length + ' WS · ' + (D.points || []).filter(p => p.type === 'cp').length + ' CP']];
    if (W >= H) {
      let sxr = W - 48 * u;
      stats.slice().reverse().forEach(([k, v]) => {
        c.font = '900 ' + (34 * u) + 'px ' + FONT; const vw = Math.max(c.measureText(v).width, 120 * u);
        c.textAlign = 'right'; c.fillStyle = '#fff'; c.fillText(v, sxr, 108 * u);
        c.fillStyle = '#9A9A9A'; c.font = '800 ' + (15 * u) + 'px ' + FONT; c.fillText(k.toUpperCase(), sxr, 66 * u);
        sxr -= vw + 54 * u;
      });
    } else {
      c.fillStyle = '#B4B4B4'; c.font = '700 ' + (22 * u) + 'px ' + FONT; c.textAlign = 'right';
      c.fillText(stats.map(s => s[1]).join('  ·  '), W - 48 * u, 112 * u);
    }

    // ---- profil elevasi ----
    if (elevH) {
      const E = {x: 70 * u, y: M.y + M.h + 70 * u, w: W - 140 * u, h: elevH - 120 * u};
      c.fillStyle = '#fff'; c.fillRect(0, M.y + M.h, W, elevH);
      c.textAlign = 'left'; c.fillStyle = '#101010'; c.font = '900 ' + (22 * u) + 'px ' + FONT; c.fillText('PROFIL ELEVASI', E.x, M.y + M.h + 36 * u);
      c.textAlign = 'right'; c.fillStyle = '#8A8A8A'; c.font = '700 ' + (18 * u) + 'px ' + FONT; c.fillText((D.gain || '') + ' · ' + fmtKm(epMax) + ' km', E.x + E.w, M.y + M.h + 36 * u);
      const eles = ep.map(p => p[1]), lo = Math.floor(Math.min(...eles) / 50) * 50, hi = Math.ceil(Math.max(...eles) / 50) * 50 || lo + 50;
      const ex = km => E.x + km / epMax * E.w, ey = e => E.y + E.h - (e - lo) / (hi - lo) * E.h;
      c.strokeStyle = '#EEEEEE'; c.lineWidth = 1.5 * u; c.fillStyle = '#9A9A9A'; c.font = '700 ' + (14 * u) + 'px ' + FONT; c.textAlign = 'right';
      const stepE = (hi - lo) > 400 ? 100 : 50;
      for (let e = lo; e <= hi; e += stepE) { c.beginPath(); c.moveTo(E.x, ey(e)); c.lineTo(E.x + E.w, ey(e)); c.stroke(); c.fillText(e + ' m', E.x - 10 * u, ey(e)); }
      c.textAlign = 'center';
      const kStep = epMax > 25 ? 5 : epMax > 12 ? 2 : 1;
      for (let k = 0; k <= epMax + .01; k += kStep) c.fillText(k + ' km', ex(k), E.y + E.h + 22 * u);
      const grad = c.createLinearGradient(0, E.y, 0, E.y + E.h); grad.addColorStop(0, D.color + '66'); grad.addColorStop(1, D.color + '05');
      c.beginPath(); c.moveTo(ex(ep[0][0]), ey(ep[0][1])); ep.forEach(p => c.lineTo(ex(p[0]), ey(p[1]))); c.lineTo(ex(epMax), E.y + E.h); c.lineTo(ex(ep[0][0]), E.y + E.h); c.closePath(); c.fillStyle = grad; c.fill();
      c.beginPath(); ep.forEach((p, i) => i ? c.lineTo(ex(p[0]), ey(p[1])) : c.moveTo(ex(p[0]), ey(p[1]))); c.strokeStyle = D.color; c.lineWidth = 4 * u; c.stroke();
      // garis pos + label bertingkat agar tidak bertumpuk
      const rows = [];
      pts.slice().sort((a, b) => a.km - b.km).forEach(p => {
        const x = ex(Number(p.km));
        c.setLineDash([7 * u, 6 * u]); c.beginPath(); c.moveTo(x, E.y - 6 * u); c.lineTo(x, E.y + E.h); c.strokeStyle = PT[p.type]; c.lineWidth = 2 * u; c.stroke(); c.setLineDash([]);
        c.font = '900 ' + (14 * u) + 'px ' + FONT; const tw2 = c.measureText(p.code).width + 18 * u;
        let row = 0; while ((rows[row] || []).some(([a, b]) => x - tw2 / 2 < b && x + tw2 / 2 > a)) row++;
        (rows[row] = rows[row] || []).push([x - tw2 / 2 - 4 * u, x + tw2 / 2 + 4 * u]);
        const ly2 = E.y - 34 * u - row * 30 * u;
        rr(c, x - tw2 / 2, ly2, tw2, 26 * u, 7 * u); c.fillStyle = PT[p.type]; c.fill();
        c.fillStyle = '#fff'; c.textAlign = 'center'; c.fillText(p.code, x, ly2 + 14 * u);
      });
    }

    // ---- footer ----
    c.fillStyle = '#101010'; c.fillRect(0, H - footH, W, footH);
    c.fillStyle = '#9A9A9A'; c.font = '700 ' + (15 * u) + 'px ' + FONT; c.textAlign = 'left';
    c.fillText(base.attr + ' · Rute dari file GPX resmi', 48 * u, H - footH / 2);
    c.textAlign = 'right'; c.fillStyle = '#fff'; c.fillText(D.url.replace(/^https?:\/\//, ''), W - 48 * u, H - footH / 2);

    return {cv, tiles: jobs.length, failed, zoom: zt};
  }


  // =====================================================================
  // GAYA POSTER — rute dominan, pin kecil, keterangan teks tanpa kotak
  // yang otomatis diletakkan di area kosong (tidak menutupi rute).
  // =====================================================================
  const FAC = [
    ['drink', '🥤', 'Minuman', /air|mineral|isotonik|minum|teh|kopi|cola|elektrolit/i],
    ['food', '🍌', 'Makanan', /buah|pisang|semangka|jeruk|rebus|makan|roti|gula|kurma|snack|kue|nasi/i],
    ['med', '⛑️', 'Medis', /medis|medic|p3k|first aid|ambulan|salep|spray|obat/i],
    ['wc', '🚻', 'Toilet', /toilet|wc|kamar mandi/i],
  ];
  const facOf = p => FAC.filter(f => (p.facilities || []).some(x => f[3].test(x)));

  async function drawBasemap(c, M, S, cx, cy, base) {
    const zt = Math.max(1, Math.min(base.max, Math.ceil(Math.log2(S))));
    const tw = 256 / 2 ** zt, tpx = tw * S, n = 2 ** zt;
    const tx0 = Math.floor((cx - M.w / 2 / S) / tw), tx1 = Math.floor((cx + M.w / 2 / S) / tw);
    const ty0 = Math.max(0, Math.floor((cy - M.h / 2 / S) / tw)), ty1 = Math.min(n - 1, Math.floor((cy + M.h / 2 / S) / tw));
    const jobs = [];
    for (let x = tx0; x <= tx1; x++) for (let y = ty0; y <= ty1; y++) jobs.push([x, y]);
    let done = 0, failed = 0;
    const q = jobs.slice();
    await Promise.all(Array.from({length: 8}, async () => {
      while (q.length) {
        const [x, y] = q.shift();
        const im = await loadImg(base.url(zt, ((x % n) + n) % n, y));
        if (im) c.drawImage(im, M.x + M.w / 2 + (x * tw - cx) * S, M.y + M.h / 2 + (y * tw - cy) * S, tpx + .6, tpx + .6); else failed++;
        done++; status('Memuat peta ' + Math.round(done / jobs.length * 100) + '% (' + done + '/' + jobs.length + ' tile)…');
      }
    }));
    return {zt, tiles: jobs.length, failed};
  }

  async function renderPoster(o) {
    const [W, H] = PRESETS[o.size];
    const u = Math.min(W, H) / 1200, land = W >= H;
    const cv = document.createElement('canvas'); cv.width = W; cv.height = H;
    const c = cv.getContext('2d');
    c.textBaseline = 'middle'; c.lineCap = 'round'; c.lineJoin = 'round';
    await document.fonts.ready;
    const dark = o.base !== 'light';                         // teks putih ber-outline di basemap gelap/ramai
    const show = Object.assign({km: true, ws: true, cp: true, sf: true, summit: true, pois: true, legend: true, scale: true}, o.show || {});
    const hidden = o.hidden || new Set();
    const pois = show.pois ? (D.pois || []).filter((p, i) => !hidden.has('poi:' + i)) : [];
    const pts = (D.points || []).filter((p, i) => show[p.type] && !hidden.has('pt:' + i));

    // area: peta penuh, profil elevasi di bawah (opsional), panel info di sisi kanan / atas
    const elevH = o.elev && ep.length ? (land ? 300 * u : 400 * u) : 0;
    const MAP = {x: 0, y: 0, w: W, h: H - elevH};
    const panel = land
      ? {x: W - 470 * u - 40 * u, y: 40 * u, w: 470 * u, h: MAP.h - 80 * u}
      : {x: 40 * u, y: 40 * u, w: W - 80 * u, h: 430 * u};
    const fit = land
      ? {x: 60 * u, y: 80 * u, w: panel.x - 120 * u, h: MAP.h - 160 * u}
      : {x: 80 * u, y: panel.y + panel.h + 90 * u, w: W - 160 * u, h: MAP.h - panel.h - 220 * u};

    // proyeksi: rute di-fit ke area "fit", tile mengisi seluruh peta
    const P = R.map(([la, ln]) => [wx(ln), wy(la)]);
    const minX = Math.min(...P.map(p => p[0])), maxX = Math.max(...P.map(p => p[0]));
    const minY = Math.min(...P.map(p => p[1])), maxY = Math.max(...P.map(p => p[1]));
    const S = Math.min(fit.w / (maxX - minX || 1e-6), fit.h / (maxY - minY || 1e-6));
    const fcx = fit.x + fit.w / 2, fcy = fit.y + fit.h / 2;
    const cx = (minX + maxX) / 2 - (fcx - (MAP.x + MAP.w / 2)) / S;   // pusat dunia untuk area peta penuh
    const cy = (minY + maxY) / 2 - (fcy - (MAP.y + MAP.h / 2)) / S;
    const pr = (la, ln) => [MAP.x + MAP.w / 2 + (wx(ln) - cx) * S, MAP.y + MAP.h / 2 + (wy(la) - cy) * S];

    c.save(); c.beginPath(); c.rect(MAP.x, MAP.y, MAP.w, MAP.h); c.clip();
    c.fillStyle = '#1f2a1f'; c.fillRect(MAP.x, MAP.y, MAP.w, MAP.h);
    const base = BASES[o.base];
    const bm = await drawBasemap(c, MAP, S, cx, cy, base);
    // gelapkan sedikit agar rute & teks kontras
    c.fillStyle = o.base === 'satellite' ? 'rgba(0,0,0,.22)' : o.base === 'topo' ? 'rgba(0,0,0,.10)' : 'rgba(255,255,255,.05)';
    c.fillRect(MAP.x, MAP.y, MAP.w, MAP.h);
    status('Menggambar rute & label…');

    // titik-titik rute (untuk menghindari teks menutupi rute)
    const routeXY = R.map(([la, ln]) => pr(la, ln));
    const occ = new Map(), cell = 18 * u;
    const mark = (x, y) => occ.set(Math.floor(x / cell) + ',' + Math.floor(y / cell), 1);
    for (let i = 1; i < routeXY.length; i++) {
      const [x1, y1] = routeXY[i - 1], [x2, y2] = routeXY[i], d = Math.hypot(x2 - x1, y2 - y1), n = Math.max(1, Math.ceil(d / (cell / 2)));
      for (let k = 0; k <= n; k++) mark(x1 + (x2 - x1) * k / n, y1 + (y2 - y1) * k / n);
    }
    const routeHits = r => { let h = 0; for (let gx = Math.floor(r.x / cell); gx <= Math.floor((r.x + r.w) / cell); gx++) for (let gy = Math.floor(r.y / cell); gy <= Math.floor((r.y + r.h) / cell); gy++) if (occ.has(gx + ',' + gy)) h++; return h; };

    // ---- ruas bahaya ----
    pois.filter(p => p.km_end).forEach(p => {
      c.beginPath();
      for (let k = p.km; k <= p.km_end; k += .02) { const [x, y] = pr(...kmLL(k)); k === p.km ? c.moveTo(x, y) : c.lineTo(x, y); }
      c.strokeStyle = p.hazard ? 'rgba(250,204,21,.55)' : 'rgba(255,255,255,.35)'; c.lineWidth = 34 * u; c.stroke();
    });

    // ---- rute: glow + outline + garis utama ----
    const path = () => { c.beginPath(); routeXY.forEach(([x, y], i) => i ? c.lineTo(x, y) : c.moveTo(x, y)); };
    c.save(); c.shadowColor = 'rgba(0,0,0,.6)'; c.shadowBlur = 18 * u; path(); c.strokeStyle = 'rgba(0,0,0,.55)'; c.lineWidth = 20 * u; c.stroke(); c.restore();
    path(); c.strokeStyle = '#ffffff'; c.lineWidth = 14 * u; c.stroke();
    if (o.color === 'grade' && ep.length) {
      for (let i = 1; i < R.length; i++) {
        const mid = (cum[i - 1] + cum[i]) / 2 / total * epMax, a = Math.max(0, mid - .1), b = Math.min(epMax, mid + .1);
        const g = b - a > .001 ? (eleAt(b) - eleAt(a)) / ((b - a) * 1000) * 100 : 0;
        c.beginPath(); c.moveTo(...routeXY[i - 1]); c.lineTo(...routeXY[i]); c.strokeStyle = gradeColor(g); c.lineWidth = 9 * u; c.stroke();
      }
    } else { path(); c.strokeStyle = D.color; c.lineWidth = 9 * u; c.stroke(); }

    // ---- panah arah di samping rute ----
    const arrowGap = Math.max(.6, Math.round(epMax / 18 * 10) / 10);
    for (let k = arrowGap / 2; k < epMax - .2; k += arrowGap) {
      const [x1, y1] = pr(...kmLL(Math.max(0, k - .05))), [x2, y2] = pr(...kmLL(Math.min(epMax, k + .05)));
      const ang = Math.atan2(y2 - y1, x2 - x1), [mx, my] = pr(...kmLL(k));
      const ox = mx + Math.cos(ang + Math.PI / 2) * 34 * u, oy = my + Math.sin(ang + Math.PI / 2) * 34 * u;
      c.save(); c.translate(ox, oy); c.rotate(ang);
      c.shadowColor = 'rgba(0,0,0,.6)'; c.shadowBlur = 8 * u; c.shadowOffsetY = 2 * u;
      c.beginPath(); c.moveTo(-15 * u, -17 * u); c.lineTo(17 * u, 0); c.lineTo(-15 * u, 17 * u); c.lineTo(-6 * u, 0); c.closePath();
      c.fillStyle = '#FACC15'; c.fill(); c.shadowColor = 'transparent';
      c.lineWidth = 4.5 * u; c.strokeStyle = '#111'; c.stroke(); c.restore();
      mark(ox, oy);
    }

    // ---- daftar marker ----
    const items = [];
    const first = R[0], last = R[R.length - 1];
    const sameSF = Math.hypot((first[0] - last[0]) * 111000, (first[1] - last[1]) * 111000) < 150;
    const kmTxt = km => fmtKm(km).replace('.', ',');
    if (show.sf) {
      items.push({kind: 'start', km: 0, ll: first, pin: 'START', color: '#16A34A', head: sameSF ? 'START · FINISH' : 'START',
        lines: [Math.round(eleAt(0)) + ' M  |  ' + (D.start || 'KM 0')], prio: 0});
      if (!sameSF) items.push({kind: 'finish', km: epMax, ll: last, pin: '🏁', color: '#101010', head: 'FINISH',
        lines: [Math.round(eleAt(epMax)) + ' M  |  ' + kmTxt(epMax) + ' KM'], prio: 0});
    }
    pts.forEach(p => items.push({kind: p.type, km: Number(p.km), ll: kmLL(Number(p.km)), pin: p.code, color: PT[p.type],
      title: p.name || p.location || (p.type === 'cp' ? 'Check Point' : 'Water Station'),
      lines: [Math.round(eleAt(Number(p.km))) + ' M  |  ' + kmTxt(p.km) + ' KM'], fac: facOf(p), cot: p.cutoff, prio: 1}));
    if (show.summit && ep.length) { let top = ep[0]; ep.forEach(q => { if (q[1] > top[1]) top = q; });
      items.push({kind: 'summit', km: top[0], ll: kmLL(top[0]), pin: '▲', color: '#101010', title: 'Titik tertinggi', lines: [Math.round(top[1]) + ' M  |  ' + kmTxt(top[0]) + ' KM'], prio: 2}); }
    pois.forEach(p => items.push({kind: 'poi', km: Number(p.km), ll: kmLL(Number(p.km)), pin: p.icon, color: p.hazard ? '#F97316' : '#374151', poi: true,
      title: p.title, lines: ['KM ' + kmTxt(p.km) + (p.km_end ? '–' + kmTxt(p.km_end) : '')], prio: 3}));

    // ---- ukuran pin & teks ----
    const pinR = 25 * u, pinTip = 20 * u;
    const fTitle = '900 ' + (25 * u) + 'px ' + FONT, fLine = '800 ' + (20 * u) + 'px ' + FONT, fHead = '900 ' + (24 * u) + 'px ' + FONT;
    const icon = 30 * u, gap = 5 * u;
    const blocked = [panel];
    items.forEach(it => { const [ax, ay] = pr(...it.ll); it.ax = ax; it.ay = ay; it.hx = ax; it.hy = it.poi ? ay : ay - pinTip - pinR; });
    // kepala pin yang saling tumpuk disebar ke samping (tangkai tetap ke titik asli)
    const pinGroups = [];
    items.filter(it => !it.poi).forEach(it => {
      const g = pinGroups.find(g => g.some(o => Math.hypot(o.hx - it.hx, o.hy - it.hy) < pinR * 2.3));
      g ? g.push(it) : pinGroups.push([it]);
    });
    pinGroups.filter(g => g.length > 1).forEach(g => {
      g.sort((a, b) => a.km - b.km);
      const mx = g.reduce((s, i) => s + i.ax, 0) / g.length, top = Math.min(...g.map(i => i.ay)) - pinTip - pinR - 26 * u;
      g.forEach((it, i) => { it.hx = mx + (i - (g.length - 1) / 2) * (pinR * 2 + 10 * u); it.hy = top; it.stem = true; });
    });
    // satu label untuk pos yang lokasinya sama & namanya sama (pin tetap terpisah)
    pinGroups.forEach(g => {
      const byTitle = {};
      g.filter(it => it.title && (it.kind === 'ws' || it.kind === 'cp')).forEach(it => (byTitle[it.title] = byTitle[it.title] || []).push(it));
      Object.values(byTitle).filter(a => a.length > 1).forEach(a => {
        a.sort((x, y) => x.km - y.km);
        const lead = a[0];
        lead.lines = a.map(it => it.pin + ' · ' + it.lines[0]);
        const facs = []; a.forEach(it => (it.fac || []).forEach(f => { if (!facs.includes(f)) facs.push(f); }));
        lead.fac = facs; lead.cot = a.map(it => it.cot).filter(Boolean)[0];
        lead.hxs = a.map(it => [it.hx, it.hy]);
        a.slice(1).forEach(it => { it.noLabel = true; });
      });
    });
    items.forEach(it => {
      const {ax, ay} = it;
      it.pr = it.poi ? {x: ax - 17 * u, y: ay - 17 * u, w: 34 * u, h: 34 * u} : {x: it.hx - pinR, y: it.hy - pinR, w: 2 * pinR, h: Math.max(2 * pinR, ay - it.hy + pinR)};
      blocked.push(it.pr);
      // ukur blok teks
      let w = 0, h = 0;
      if (it.head) { c.font = fHead; w = Math.max(w, c.measureText(it.head).width + 36 * u); h += 40 * u; }
      if (it.title) { c.font = fTitle; w = Math.max(w, c.measureText(it.title.toUpperCase()).width); h += 30 * u; }
      c.font = fLine; it.lines.forEach(l => { w = Math.max(w, c.measureText(l).width); h += 25 * u; });
      const extra = (it.fac || []).length + (it.cot ? 1 : 0);
      if (extra) { w = Math.max(w, (it.fac || []).length * (icon + gap) + (it.cot ? 96 * u : 0)); h += icon + 8 * u; }
      it.tw = w; it.th = h;
    });
    // badge KM
    const kmBadges = [];
    if (show.km) {
      const a = pr(...kmLL(0)), b = pr(...kmLL(Math.min(1, epMax)));
      const step = Math.hypot(a[0] - b[0], a[1] - b[1]) < 80 * u ? 2 : 1;
      for (let k = step; k < epMax - .3; k += step) {
        const [x, y] = pr(...kmLL(k)), r = {x: x - 15 * u, y: y - 15 * u, w: 30 * u, h: 30 * u, k, cx: x, cy: y};
        if (!blocked.some(b2 => hit(b2, r))) { kmBadges.push(r); blocked.push(r); }
      }
    }

    // ---- penempatan teks: cari tempat kosong di sekitar pin, hindari rute & label lain ----
    const placed = [];
    const overlapArea = (a, b) => Math.max(0, Math.min(a.x + a.w, b.x + b.w) - Math.max(a.x, b.x)) * Math.max(0, Math.min(a.y + a.h, b.y + b.h) - Math.max(a.y, b.y));
    items.filter(it => !it.noLabel).sort((a, b) => a.prio - b.prio || a.km - b.km).forEach(it => {
      const heads = it.hxs || [[it.hx, it.hy]];
      const hx = heads.reduce((s, h) => s + h[0], 0) / heads.length, hy = Math.min(...heads.map(h => h[1]));
      const gw = heads.length > 1 ? (Math.max(...heads.map(h => h[0])) - Math.min(...heads.map(h => h[0]))) / 2 : 0;
      let best = null;
      [50, 100, 160, 230, 310, 400, 500].forEach(d => {
        for (let a = 0; a < 24; a++) {
          const ang = a / 24 * Math.PI * 2, right = Math.cos(ang) >= 0;
          const px = hx + Math.cos(ang) * (d + gw), py = hy + Math.sin(ang) * d * .8;
          const r = {x: right ? px : px - it.tw, y: py - it.th / 2, w: it.tw, h: it.th};
          if (r.x < 24 * u || r.y < 24 * u || r.x + r.w > MAP.w - 24 * u || r.y + r.h > MAP.h - 24 * u) continue;
          const pad = {x: r.x - 12 * u, y: r.y - 10 * u, w: r.w + 24 * u, h: r.h + 20 * u};
          if (hit(panel, pad)) continue;
          let clash = 0;
          blocked.concat(placed).forEach(b2 => { clash += overlapArea(b2, pad); });
          const score = clash * 50 / (u * u) + routeHits(r) * 40 + d * .6 + (Math.abs(Math.sin(ang)) > .9 ? 25 : 0);
          if (!best || score < best.score) best = {r, score, right};
        }
      });
      if (!best) best = {r: {x: hx + 40 * u, y: hy - it.th / 2, w: it.tw, h: it.th}, right: true};
      it.box = best.r; it.right = best.right; it.lhx = hx; it.lhy = hy; placed.push(best.r);
    });

    // ---- garis penunjuk ----
    items.filter(it => it.box).forEach(it => {
      const b = it.box, hx = it.lhx, hy = it.lhy;
      const tx = it.right ? b.x - 8 * u : b.x + b.w + 8 * u, ty = Math.max(b.y + 14 * u, Math.min(b.y + b.h - 14 * u, hy));
      if (Math.hypot(tx - hx, ty - hy) < pinR + 20 * u) return;
      c.beginPath(); c.moveTo(hx, hy); c.lineTo(tx, ty); c.setLineDash([3 * u, 7 * u]);
      c.strokeStyle = dark ? 'rgba(255,255,255,.95)' : 'rgba(16,16,16,.85)'; c.lineWidth = 3.5 * u; c.stroke(); c.setLineDash([]);
    });

    // ---- badge KM ----
    kmBadges.forEach(r => {
      c.beginPath(); c.arc(r.cx, r.cy, 13 * u, 0, 7); c.fillStyle = 'rgba(255,255,255,.95)'; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = '#101010'; c.stroke();
      c.fillStyle = '#101010'; c.font = '900 ' + (13 * u) + 'px ' + FONT; c.textAlign = 'center'; c.fillText(String(r.k), r.cx, r.cy + u);
    });

    // ---- pin ----
    items.forEach(it => {
      const {ax, ay} = it;
      c.save(); c.shadowColor = 'rgba(0,0,0,.55)'; c.shadowBlur = 12 * u; c.shadowOffsetY = 4 * u;
      if (it.poi) {
        c.beginPath(); c.arc(ax, ay, 17 * u, 0, 7); c.fillStyle = '#fff'; c.fill(); c.restore();
        c.lineWidth = 4 * u; c.strokeStyle = it.color; c.beginPath(); c.arc(ax, ay, 17 * u, 0, 7); c.stroke();
        c.font = (18 * u) + 'px sans-serif'; c.textAlign = 'center'; c.fillText(it.pin, ax, ay + u);
        return;
      }
      const hx = it.hx, hy = it.hy;
      if (it.stem) {
        // tangkai dari titik asli ke kepala pin yang digeser
        c.restore();
        c.beginPath(); c.moveTo(ax, ay); c.lineTo(hx, hy + pinR * .8); c.lineWidth = 7 * u; c.strokeStyle = 'rgba(0,0,0,.55)'; c.stroke();
        c.lineWidth = 3.5 * u; c.strokeStyle = '#fff'; c.stroke();
        c.save(); c.shadowColor = 'rgba(0,0,0,.55)'; c.shadowBlur = 12 * u; c.shadowOffsetY = 4 * u;
        c.beginPath(); c.arc(hx, hy, pinR, 0, 7); c.fillStyle = it.color; c.fill(); c.restore();
        c.beginPath(); c.arc(hx, hy, pinR, 0, 7);
      } else {
        c.beginPath(); c.moveTo(ax, ay); c.lineTo(ax - pinR * .62, hy + pinR * .72); c.arc(ax, hy, pinR, Math.PI * .75, Math.PI * .25); c.closePath();
        c.fillStyle = it.color; c.fill(); c.restore();
      }
      c.lineWidth = 4 * u; c.strokeStyle = '#fff'; c.stroke();
      c.beginPath(); c.arc(ax, ay, 6 * u, 0, 7); c.fillStyle = '#fff'; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = it.color; c.stroke();
      c.fillStyle = '#fff'; c.textAlign = 'center';
      const lab = String(it.pin);
      c.font = (lab.length > 3 ? '900 ' + (11 * u) : lab.length > 2 ? '900 ' + (14 * u) : '900 ' + (17 * u)) + 'px ' + FONT;
      c.fillText(lab, hx, hy + u);
    });

    // ---- teks keterangan (tanpa kotak) ----
    const halo = (txt, x, y) => {
      c.lineWidth = 7 * u; c.strokeStyle = dark ? 'rgba(0,0,0,.85)' : 'rgba(255,255,255,.95)'; c.strokeText(txt, x, y);
      c.fillStyle = dark ? '#ffffff' : '#101010'; c.fillText(txt, x, y);
    };
    items.filter(it => it.box).forEach(it => {
      const b = it.box; let y = b.y;
      const ax = it.right ? b.x : b.x + b.w;
      c.textAlign = it.right ? 'left' : 'right';
      if (it.head) {
        c.font = fHead; const hw = c.measureText(it.head).width + 36 * u;
        const hx = it.right ? b.x : b.x + b.w - hw;
        c.fillStyle = it.kind === 'finish' ? '#DC2626' : '#16A34A'; c.fillRect(hx, y, hw, 36 * u);
        c.fillStyle = '#fff'; c.textAlign = 'center'; c.fillText(it.head, hx + hw / 2, y + 19 * u); c.textAlign = it.right ? 'left' : 'right';
        y += 40 * u;
      }
      if (it.title) { c.font = fTitle; halo(it.title.toUpperCase(), ax, y + 15 * u); y += 30 * u; }
      c.font = fLine; it.lines.forEach(l => { halo(l, ax, y + 12 * u); y += 25 * u; });
      const fac = it.fac || [];
      if (fac.length || it.cot) {
        y += 4 * u;
        const rowW = fac.length * (icon + gap) + (it.cot ? 96 * u : 0);
        let x = it.right ? b.x : b.x + b.w - rowW;
        fac.forEach(f => {
          c.save(); c.shadowColor = 'rgba(0,0,0,.4)'; c.shadowBlur = 6 * u; rr(c, x, y, icon, icon, 7 * u); c.fillStyle = '#fff'; c.fill(); c.restore();
          c.font = (19 * u) + 'px sans-serif'; c.textAlign = 'center'; c.fillStyle = '#000'; c.fillText(f[1], x + icon / 2, y + icon / 2 + u);
          x += icon + gap;
        });
        if (it.cot) {
          rr(c, x, y, 90 * u, icon, 7 * u); c.fillStyle = '#DC2626'; c.fill();
          c.fillStyle = '#fff'; c.font = '900 ' + (15 * u) + 'px ' + FONT; c.textAlign = 'center'; c.fillText('COT ' + it.cot, x + 45 * u, y + icon / 2 + u);
        }
      }
    });

    // ---- panel info ----
    c.save(); c.shadowColor = 'rgba(0,0,0,.45)'; c.shadowBlur = 40 * u;
    rr(c, panel.x, panel.y, panel.w, panel.h, 26 * u); c.fillStyle = 'rgba(12,12,12,.82)'; c.fill(); c.restore();
    const px = panel.x + 40 * u; let py = panel.y + 52 * u;
    c.textAlign = 'left';
    c.fillStyle = '#FF8A80'; c.font = '800 ' + (18 * u) + 'px ' + FONT; c.fillText(('GERUNG TRAIL RUN 2026 · ' + (D.tag || '')).toUpperCase(), px, py);
    const maxEle = ep.length ? Math.round(Math.max(...ep.map(q => q[1]))) : null;
    const facUsed = FAC.filter(f => pts.some(p => facOf(p).includes(f)));
    if (land) {
      py += 110 * u;
      c.fillStyle = '#fff'; c.font = '900 ' + (150 * u) + 'px ' + FONT; c.fillText((D.distance || '').replace(/\s+/g, ''), px - 6 * u, py);
      py += 100 * u;
      const rows = [['Jarak', fmtKm(epMax) + ' KM'], ['Elevation gain', D.gain || '-'], ['Titik tertinggi', maxEle !== null ? maxEle + ' MDPL' : '-'], ['Start', D.start || '-'], ['Cut-off time', D.cot || '-']];
      rows.forEach(([k, v]) => {
        c.fillStyle = 'rgba(255,255,255,.08)'; rr(c, px, py - 26 * u, panel.w - 80 * u, 52 * u, 10 * u); c.fill();
        c.fillStyle = '#9A9A9A'; c.font = '800 ' + (15 * u) + 'px ' + FONT; c.textAlign = 'left'; c.fillText(k.toUpperCase(), px + 16 * u, py);
        c.fillStyle = '#fff'; c.font = '900 ' + (24 * u) + 'px ' + FONT; c.textAlign = 'right'; c.fillText(v, px + panel.w - 96 * u, py + u);
        py += 62 * u;
      });
      c.textAlign = 'left';
    } else {
      py += 120 * u;
      c.fillStyle = '#fff'; c.font = '900 ' + (130 * u) + 'px ' + FONT; c.fillText((D.distance || '').replace(/\s+/g, ''), px - 6 * u, py);
      c.font = '800 ' + (24 * u) + 'px ' + FONT; c.fillStyle = '#fff'; c.textAlign = 'right';
      [['Elevation gain', D.gain || '-'], ['Tertinggi', maxEle !== null ? maxEle + ' MDPL' : '-'], ['Cut-off', D.cot || '-']].forEach(([k, v], i) => {
        const yy = panel.y + 120 * u + i * 62 * u;
        c.fillStyle = '#9A9A9A'; c.font = '800 ' + (15 * u) + 'px ' + FONT; c.fillText(k.toUpperCase(), panel.x + panel.w - 40 * u, yy - 18 * u);
        c.fillStyle = '#fff'; c.font = '900 ' + (28 * u) + 'px ' + FONT; c.fillText(v, panel.x + panel.w - 40 * u, yy + 10 * u);
      });
      c.textAlign = 'left'; py += 60 * u;
    }
    if (show.legend) {
      const lg = [];
      if (pts.some(p => p.type === 'ws')) lg.push(['pin', PT.ws, 'Water Station']);
      if (pts.some(p => p.type === 'cp')) lg.push(['pin', PT.cp, 'Check Point']);
      facUsed.forEach(f => lg.push(['fac', f[1], f[2]]));
      if (pts.some(p => p.cutoff)) lg.push(['cot', '', 'Cut-off di pos']);
      if (pois.length) lg.push(['poi', '#F97316', 'Info & bahaya jalur']);
      lg.push(['arrow', '', 'Arah lari']);
      if (show.km) lg.push(['km', '', 'Penanda KM']);
      py += 14 * u;
      let lh = 44 * u, cols = land ? 1 : 2;
      const room = panel.y + panel.h - 30 * u - py;
      if (Math.ceil(lg.length / cols) * lh > room) cols = 2;
      if (Math.ceil(lg.length / cols) * lh > room) lh = Math.max(30 * u, room / Math.ceil(lg.length / cols));
      const colW = (panel.w - 80 * u) / cols;
      lg.forEach((L, i) => {
        const x = px + (i % cols) * colW, y = py + Math.floor(i / cols) * lh;
        if (L[0] === 'pin') { c.beginPath(); c.arc(x + 15 * u, y, 13 * u, 0, 7); c.fillStyle = L[1]; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = '#fff'; c.stroke(); }
        else if (L[0] === 'fac') { rr(c, x, y - 15 * u, 30 * u, 30 * u, 7 * u); c.fillStyle = '#fff'; c.fill(); c.font = (18 * u) + 'px sans-serif'; c.textAlign = 'center'; c.fillStyle = '#000'; c.fillText(L[1], x + 15 * u, y + u); c.textAlign = 'left'; }
        else if (L[0] === 'cot') { rr(c, x, y - 15 * u, 30 * u, 30 * u, 7 * u); c.fillStyle = '#DC2626'; c.fill(); c.fillStyle = '#fff'; c.font = '900 ' + (10 * u) + 'px ' + FONT; c.textAlign = 'center'; c.fillText('COT', x + 15 * u, y + u); c.textAlign = 'left'; }
        else if (L[0] === 'poi') { c.beginPath(); c.arc(x + 15 * u, y, 13 * u, 0, 7); c.fillStyle = '#fff'; c.fill(); c.lineWidth = 4 * u; c.strokeStyle = L[1]; c.stroke(); }
        else if (L[0] === 'arrow') { c.save(); c.translate(x + 15 * u, y); c.beginPath(); c.moveTo(-12 * u, -9 * u); c.lineTo(10 * u, 0); c.lineTo(-12 * u, 9 * u); c.lineTo(-6 * u, 0); c.closePath(); c.fillStyle = '#FACC15'; c.fill(); c.restore(); }
        else { c.beginPath(); c.arc(x + 15 * u, y, 12 * u, 0, 7); c.fillStyle = '#fff'; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = '#101010'; c.stroke(); }
        c.fillStyle = '#fff'; c.font = '800 ' + ((cols > 1 && land ? 14 : 19) * u) + 'px ' + FONT; c.textAlign = 'left'; c.fillText(L[2].toUpperCase(), x + 44 * u, y + u);
      });
    }

    // ---- skala & utara ----
    if (show.scale) {
      const latC = (Math.min(...R.map(q => q[0])) + Math.max(...R.map(q => q[0]))) / 2;
      const mpp = 40075016.686 * Math.cos(rad(latC)) / 256 / S;
      const nice = [100, 200, 250, 500, 1000, 2000, 5000].find(m => m / mpp > 140 * u) || 5000;
      const sw = nice / mpp, sx = 40 * u, sy = MAP.h - 46 * u;
      c.fillStyle = dark ? '#fff' : '#101010'; c.fillRect(sx, sy, sw, 6 * u); c.fillRect(sx, sy - 8 * u, 3 * u, 14 * u); c.fillRect(sx + sw - 3 * u, sy - 8 * u, 3 * u, 14 * u);
      c.font = '800 ' + (17 * u) + 'px ' + FONT; c.textAlign = 'left'; halo(nice >= 1000 ? nice / 1000 + ' KM' : nice + ' M', sx, sy - 20 * u);
      const nx = (land ? panel.x - 70 * u : W - 70 * u), ny = land ? 80 * u : panel.y + panel.h + 70 * u;
      c.beginPath(); c.moveTo(nx, ny - 30 * u); c.lineTo(nx + 15 * u, ny + 10 * u); c.lineTo(nx, ny + 2 * u); c.lineTo(nx - 15 * u, ny + 10 * u); c.closePath(); c.fillStyle = '#E53935'; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = '#fff'; c.stroke();
      c.textAlign = 'center'; c.font = '900 ' + (18 * u) + 'px ' + FONT; halo('N', nx, ny + 30 * u);
    }
    // atribusi
    c.font = '700 ' + (13 * u) + 'px ' + FONT; c.textAlign = 'right';
    halo(base.attr + ' · ' + D.url.replace(/^https?:\/\//, ''), (land ? panel.x - 30 * u : W - 40 * u), MAP.h - 24 * u);
    c.restore();

    // ---- profil elevasi (gelap, menyatu dengan poster) ----
    if (elevH) {
      const top = MAP.h;
      c.fillStyle = '#0E0E0E'; c.fillRect(0, top, W, elevH);
      const E = {x: 110 * u, y: top + 86 * u, w: W - 180 * u, h: elevH - 140 * u};
      c.textAlign = 'left'; c.fillStyle = '#fff'; c.font = '900 ' + (22 * u) + 'px ' + FONT; c.fillText('PROFIL ELEVASI', 40 * u, top + 38 * u);
      c.textAlign = 'right'; c.fillStyle = '#9A9A9A'; c.font = '700 ' + (18 * u) + 'px ' + FONT; c.fillText((D.gain || '') + ' · ' + fmtKm(epMax) + ' km', W - 40 * u, top + 38 * u);
      const eles = ep.map(q => q[1]), lo = Math.floor(Math.min(...eles) / 50) * 50, hi = Math.ceil(Math.max(...eles) / 50) * 50 || lo + 50;
      const ex = km => E.x + km / epMax * E.w, ey = e => E.y + E.h - (e - lo) / (hi - lo) * E.h;
      c.strokeStyle = 'rgba(255,255,255,.08)'; c.lineWidth = 1.5 * u; c.fillStyle = '#7A7A7A'; c.font = '700 ' + (14 * u) + 'px ' + FONT; c.textAlign = 'right';
      const stepE = (hi - lo) > 400 ? 100 : 50;
      for (let e = lo; e <= hi; e += stepE) { c.beginPath(); c.moveTo(E.x, ey(e)); c.lineTo(E.x + E.w, ey(e)); c.stroke(); c.fillText(e + ' m', E.x - 12 * u, ey(e)); }
      c.textAlign = 'center';
      const kStep = epMax > 25 ? 5 : epMax > 12 ? 2 : 1;
      for (let k = 0; k <= epMax + .01; k += kStep) c.fillText(k + ' km', ex(k), E.y + E.h + 24 * u);
      const grad = c.createLinearGradient(0, E.y, 0, E.y + E.h); grad.addColorStop(0, D.color + 'AA'); grad.addColorStop(1, D.color + '10');
      c.beginPath(); c.moveTo(ex(ep[0][0]), ey(ep[0][1])); ep.forEach(q => c.lineTo(ex(q[0]), ey(q[1]))); c.lineTo(ex(epMax), E.y + E.h); c.lineTo(ex(ep[0][0]), E.y + E.h); c.closePath(); c.fillStyle = grad; c.fill();
      c.beginPath(); ep.forEach((q, i) => i ? c.lineTo(ex(q[0]), ey(q[1])) : c.moveTo(ex(q[0]), ey(q[1]))); c.strokeStyle = '#fff'; c.lineWidth = 4 * u; c.stroke();
      const rows = [];
      pts.slice().sort((a, b) => a.km - b.km).forEach(p => {
        const x = ex(Number(p.km)), y = ey(eleAt(Number(p.km)));
        c.setLineDash([6 * u, 6 * u]); c.beginPath(); c.moveTo(x, E.y - 4 * u); c.lineTo(x, E.y + E.h); c.strokeStyle = PT[p.type]; c.lineWidth = 2 * u; c.stroke(); c.setLineDash([]);
        c.beginPath(); c.arc(x, y, 7 * u, 0, 7); c.fillStyle = PT[p.type]; c.fill(); c.lineWidth = 3 * u; c.strokeStyle = '#fff'; c.stroke();
        c.font = '900 ' + (14 * u) + 'px ' + FONT; const tw2 = c.measureText(p.code).width + 18 * u;
        let row = 0; while ((rows[row] || []).some(([a, b]) => x - tw2 / 2 < b && x + tw2 / 2 > a)) row++;
        (rows[row] = rows[row] || []).push([x - tw2 / 2 - 4 * u, x + tw2 / 2 + 4 * u]);
        const ly = E.y - 32 * u - row * 30 * u;
        rr(c, x - tw2 / 2, ly, tw2, 26 * u, 7 * u); c.fillStyle = PT[p.type]; c.fill();
        c.fillStyle = '#fff'; c.textAlign = 'center'; c.fillText(p.code, x, ly + 14 * u);
      });
    }
    return {cv, tiles: bm.tiles, failed: bm.failed, zoom: bm.zt};
  }

  // ---- pilihan marker: per jenis & per item ----
  const typeBox = $('types'), itemBox = $('items');
  const ICOLOR = {ws: PT.ws, cp: PT.cp};
  (D.points || []).forEach((p, i) => itemBox.insertAdjacentHTML('beforeend',
    '<button type="button" class="on" data-item="pt:' + i + '" data-kind="' + p.type + '" style="--c:' + ICOLOR[p.type] + '"><i></i>' + p.code + ' · KM ' + fmtKm(p.km) + '</button>'));
  (D.pois || []).forEach((p, i) => itemBox.insertAdjacentHTML('beforeend',
    '<button type="button" class="on" data-item="poi:' + i + '" data-kind="pois" style="--c:' + (p.hazard ? '#F97316' : '#9CA3AF') + '"><i></i>' + p.icon + ' ' + String(p.title).replace(/</g, '&lt;') + '</button>'));
  if (!itemBox.children.length) itemBox.remove();
  const syncItems = () => itemBox.querySelectorAll && itemBox.querySelectorAll('[data-item]').forEach(b => {
    const typeOn = typeBox.querySelector('[data-show="' + b.dataset.kind + '"]').classList.contains('on');
    b.disabled = !typeOn;
  });
  box.addEventListener('click', e => {
    const b = e.target.closest('[data-show],[data-item]');
    if (!b || b.disabled) return;
    b.classList.toggle('on');
    syncItems();
  });
  if (!(D.pois || []).length) typeBox.querySelector('[data-show="pois"]').remove();
  const markerOpts = () => {
    const show = {};
    typeBox.querySelectorAll('[data-show]').forEach(b => { show[b.dataset.show] = b.classList.contains('on'); });
    const hidden = new Set([...box.querySelectorAll('[data-item]:not(.on)')].map(b => b.dataset.item));
    return {show, hidden};
  };

  let lastUrl = null;
  function status(t) { $('status').textContent = t; }
  $('go').addEventListener('click', async () => {
    const o = Object.assign({size: $('size').value, base: $('base').value, style: $('style').value, color: $('color').value, elev: $('elev').checked}, markerOpts());
    $('go').disabled = true;
    try {
      const {cv, tiles, failed, zoom} = await (o.style === 'poster' ? renderPoster(o) : render(o));
      const blob = await new Promise(ok => cv.toBlob(ok, 'image/png'));
      if (lastUrl) URL.revokeObjectURL(lastUrl);
      lastUrl = URL.createObjectURL(blob);
      const name = 'course-map-gtr2026-' + (D.slug || 'kategori') + '-' + o.style + '-' + o.size + '-' + o.base + '.png';
      $('img').src = lastUrl; $('dl').href = lastUrl; $('dl').setAttribute('download', name);
      $('info').textContent = cv.width + ' × ' + cv.height + ' px · ' + (blob.size / 1048576).toFixed(1) + ' MB · zoom tile ' + zoom + (failed ? ' · ' + failed + ' tile gagal dimuat' : '');
      res.classList.add('on');
      status(failed ? 'Selesai, tapi ' + failed + ' tile peta gagal dimuat (area itu tampil polos). Coba lagi atau ganti basemap.' : 'Selesai ✓ — klik "Download PNG" untuk menyimpan.');
      res.scrollIntoView({behavior: 'smooth', block: 'start'});
    } catch (e) {
      console.error(e);
      status('Gagal membuat gambar: ' + e.message);
    } finally {
      $('go').disabled = false;
    }
  });
})();
</script>
@endverbatim
@endpush
