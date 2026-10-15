@extends('layouts.gtr')

@section('title', 'Lacak ' . $data['name'] . ' (BIB ' . $data['bib'] . ') — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
  .trk{padding:40px 0 64px}
  .trk-back{display:inline-flex;gap:6px;font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-soft);margin-bottom:16px}
  .trk-card{display:grid;grid-template-columns:auto 1fr;gap:16px;align-items:center;padding:18px;border-radius:20px;background:var(--bg-soft);border:1px solid var(--line)}
  .trk-bibbig{padding:12px 14px;border-radius:14px;background:#fff;color:#101010;text-align:center;font-family:'Archivo',sans-serif;line-height:1}
  .trk-bibbig small{display:block;font-weight:800;font-size:9px;letter-spacing:.16em;color:#8A8A8A}
  .trk-bibbig b{display:block;font-weight:900;font-size:26px;margin-top:4px}
  .trk-name{font-family:'Archivo',sans-serif;font-weight:900;font-size:clamp(20px,4vw,28px);line-height:1.1;text-transform:uppercase}
  .trk-meta{color:var(--text-mute);font-size:13px;margin-top:4px}
  .trk-stat{grid-column:1 / -1;display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
  .trk-stat div{padding:12px;border-radius:14px;background:rgba(255,255,255,.04);border:1px solid var(--line)}
  .trk-stat small{display:block;font-family:'Archivo',sans-serif;font-weight:700;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--text-mute)}
  .trk-stat b{display:block;font-family:'Archivo',sans-serif;font-weight:900;font-size:20px;margin-top:4px;font-variant-numeric:tabular-nums}
  .trk-pill{display:inline-flex;align-items:center;gap:7px;padding:6px 12px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:800;font-size:11px;letter-spacing:.06em;text-transform:uppercase}
  .trk-pill i{width:8px;height:8px;border-radius:50%;background:currentColor}
  .st-not_started{background:rgba(255,255,255,.08);color:#B4B4B4}
  .st-on_course{background:rgba(34,197,94,.14);color:#4ADE80}
  .st-finished{background:rgba(229,57,53,.16);color:#FF8A80}
  .st-dnf,.st-dq,.st-dns{background:rgba(255,255,255,.08);color:#9CA3AF}
  #trk-map{height:420px;border-radius:20px;overflow:hidden;margin-top:16px;background:#dfe5d8;isolation:isolate}
  .trk-bar{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;gap:10px;margin-top:12px;color:var(--text-mute);font-size:12.5px}
  .trk-share{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:999px;background:#fff;color:#101010;font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;border:none;cursor:pointer}
  .trk-tl{margin-top:22px;display:flex;flex-direction:column;gap:8px}
  .tl-row{display:grid;grid-template-columns:auto 1fr auto;gap:14px;align-items:center;padding:13px 16px;border-radius:16px;background:var(--bg-soft);border:1px solid var(--line)}
  .tl-row.done{border-color:rgba(34,197,94,.35)}
  .tl-dot{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.08);color:#9CA3AF;font-size:14px;font-weight:900}
  .tl-row.done .tl-dot{background:#22C55E;color:#fff}
  .tl-row b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:15px}
  .tl-row small{color:var(--text-mute);font-size:12px}
  .tl-time{text-align:right}
  .tl-time b{font-variant-numeric:tabular-nums}
  .tl-delta{display:inline-block;margin-top:3px;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:800}
  .tl-delta.fast{background:rgba(34,197,94,.14);color:#4ADE80}
  .tl-delta.slow{background:rgba(249,115,22,.14);color:#FDBA74}
  .trk-note{margin-top:14px;color:var(--text-mute);font-size:12px;line-height:1.5}
  .rn{width:22px;height:22px;margin:-11px 0 0 -11px;border-radius:50%;background:#E53935;border:3px solid #fff;box-shadow:0 0 0 0 rgba(229,57,53,.6);animation:rnPulse 1.8s infinite}
  @keyframes rnPulse{70%{box-shadow:0 0 0 16px rgba(229,57,53,0)}100%{box-shadow:0 0 0 0 rgba(229,57,53,0)}}
  .tpm{transform:translate(-50%,-50%);padding:3px 8px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:900;font-size:10.5px;color:#fff;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35);white-space:nowrap;display:inline-block}
  @media (min-width:820px){
    .trk-card{grid-template-columns:auto 1fr auto}
    .trk-stat{grid-column:auto;min-width:420px}
    #trk-map{height:480px}
  }
</style>
@endpush

@section('content')
<section class="trk">
  <div class="wrap">
    <a class="trk-back" href="{{ route('gtr.track.search') }}">← Cari pelari lain</a>

    <div class="trk-card">
      <div class="trk-bibbig"><small>BIB</small><b>{{ $data['bib'] }}</b></div>
      <div>
        <div class="trk-name">{{ $data['name'] }}</div>
        <div class="trk-meta">{{ $cat->distance }} · {{ $cat->name }}{{ $data['club'] ? ' · ' . $data['club'] : '' }}</div>
        <div style="margin-top:8px"><span class="trk-pill" data-trk-status><i></i><span></span></span></div>
      </div>
      <div class="trk-stat">
        <div><small data-trk-time-lab>Waktu</small><b data-trk-time>-</b></div>
        <div><small>Posisi</small><b data-trk-pos>-</b></div>
        <div><small>Target</small><b data-trk-target>-</b></div>
      </div>
    </div>

    <div id="trk-map"
         data-points='@json($cat->route_points ?? [])'
         data-ep-max="{{ $data['total_km'] }}"
         data-color="{{ $cat->color }}"></div>

    <div class="trk-bar">
      <span>Diperbarui <b data-trk-updated>{{ $data['updated'] }}</b> WITA · otomatis tiap 30 detik</span>
      <button type="button" class="trk-share" data-trk-share>Bagikan Link</button>
    </div>

    <div class="trk-tl" data-trk-tl></div>

    <p class="trk-note">Waktu tercatat saat BIB di-scan marshal di tiap pos. Posisi di antara pos adalah <b>perkiraan</b> berdasarkan pace sejauh ini dan race plan, bukan GPS. Hasil resmi mengikuti pengumuman panitia.</p>
  </div>
</section>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
  window.__trk = @json($data);
  window.__trkUrl = @json(route('gtr.track.data', $data['bib']));
</script>
@verbatim
<script>
  (function () {
    const el = document.getElementById('trk-map');
    const pts = JSON.parse(el.dataset.points || '[]');
    const epMax = Number(el.dataset.epMax) || 1;
    const COLOR = {start:'#22C55E', checkpoint:'#F97316', water_station:'#2EA6E6', finish:'#101010'};
    const STATUS = {not_started:'Belum start', on_course:'Di lintasan', finished:'Finish', dnf:'DNF', dq:'DQ', dns:'DNS'};
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const hms = s => { s = Math.max(0, Math.round(s)); return Math.floor(s / 3600) + ':' + String(Math.floor(s % 3600 / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); };
    const hm = m => Math.floor(m / 60) + ':' + String(Math.round(m % 60)).padStart(2, '0');
    const fmtKm = v => (Math.round(v * 10) / 10).toString();

    // ---- peta ----
    let map = null, cum = [0], total = 1, tpLayer = null, runner = null, done = null;
    if (pts.length && window.L) {
      map = L.map(el, {scrollWheelZoom:false, attributionControl:false, dragging: !window.matchMedia('(pointer:coarse)').matches, tap:false});
      L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {maxZoom:19, subdomains:'abcd'}).addTo(map);
      const toRad = d => d * Math.PI / 180;
      for (let i = 1; i < pts.length; i++) {
        const [a1, o1] = pts[i - 1], [a2, o2] = pts[i];
        const h = Math.sin(toRad(a2 - a1) / 2) ** 2 + Math.cos(toRad(a1)) * Math.cos(toRad(a2)) * Math.sin(toRad(o2 - o1) / 2) ** 2;
        cum[i] = cum[i - 1] + 2 * 6371 * Math.asin(Math.min(1, Math.sqrt(h)));
      }
      total = cum[cum.length - 1] || 1;
      L.polyline(pts, {color:'#101010', weight:8, opacity:.35}).addTo(map);
      L.polyline(pts, {color:'#9CA3AF', weight:4.5}).addTo(map);
      done = L.polyline([], {color: el.dataset.color || '#E53935', weight:5}).addTo(map);
      map.fitBounds(L.polyline(pts).getBounds(), {padding:[30, 30]});
      tpLayer = L.layerGroup().addTo(map);
    }
    const kmLL = km => {
      const k = Math.max(0, Math.min(total, km / epMax * total));
      let j = 1; while (j < cum.length && cum[j] < k) j++;
      if (j >= cum.length) j = cum.length - 1;
      const seg = cum[j] - cum[j - 1], t = seg ? (k - cum[j - 1]) / seg : 0;
      return [pts[j - 1][0] + (pts[j][0] - pts[j - 1][0]) * t, pts[j - 1][1] + (pts[j][1] - pts[j - 1][1]) * t];
    };
    const pathTo = km => { const out = []; for (let k = 0; k < km; k += .05) out.push(kmLL(k)); out.push(kmLL(km)); return out; };

    // ---- render ----
    let data = window.__trk, tick = null;
    function render() {
      const st = document.querySelector('[data-trk-status]');
      st.className = 'trk-pill st-' + data.status;
      st.querySelector('span').textContent = STATUS[data.status] || data.status;
      document.querySelector('[data-trk-target]').textContent = data.target ? hm(data.target) : '-';
      document.querySelector('[data-trk-updated]').textContent = data.updated;

      const passed = data.passes.filter(p => p.passed && p.km != null).sort((a, b) => a.km - b.km);
      const last = passed[passed.length - 1];
      const posKm = data.status === 'finished' ? data.total_km : (data.estimate ? data.estimate.km : (last ? last.km : 0));
      document.querySelector('[data-trk-pos]').textContent = data.status === 'not_started' ? '-' : 'KM ' + fmtKm(posKm);

      // waktu: net bila finish, berjalan bila di lintasan
      clearInterval(tick);
      const timeEl = document.querySelector('[data-trk-time]'), labEl = document.querySelector('[data-trk-time-lab]');
      if (data.status === 'finished' && data.net != null) { labEl.textContent = 'Waktu Finish'; timeEl.textContent = hms(data.net); }
      else if (data.status === 'on_course' && data.start_ts) {
        labEl.textContent = 'Waktu Berjalan';
        const skew = Date.now() - data.now_ts;
        const upd = () => { timeEl.textContent = hms((Date.now() - skew - data.start_ts) / 1000); };
        upd(); tick = setInterval(upd, 1000);
      } else { labEl.textContent = 'Waktu'; timeEl.textContent = '-'; }

      // timeline
      document.querySelector('[data-trk-tl]').innerHTML = data.passes.map(p => {
        let delta = '';
        if (p.delta != null) {
          const m = Math.round(Math.abs(p.delta) / 60);
          delta = m < 1 ? '<span class="tl-delta fast">Sesuai plan</span>'
            : '<span class="tl-delta ' + (p.delta < 0 ? 'fast' : 'slow') + '">' + hm(m) + (p.delta < 0 ? ' lebih cepat' : ' lebih lambat') + '</span>';
        }
        return '<div class="tl-row' + (p.passed ? ' done' : '') + '">'
          + '<span class="tl-dot">' + (p.passed ? '✓' : '') + '</span>'
          + '<div><b>' + esc(p.code) + ' · ' + esc(p.name) + '</b><small>' + (p.km != null ? 'KM ' + fmtKm(p.km) : '') + (p.cutoff ? ' · COT ' + esc(p.cutoff) : '') + (!p.passed && p.plan_elapsed != null ? ' · plan +' + hms(p.plan_elapsed) : '') + '</small></div>'
          + '<div class="tl-time">' + (p.passed ? '<b>' + esc(p.at) + '</b><br><small>+' + hms(p.elapsed || 0) + '</small><br>' + delta : '<small>Belum lewat</small>') + '</div>'
          + '</div>';
      }).join('');

      // peta
      if (map) {
        tpLayer.clearLayers();
        data.passes.filter(p => p.km != null).forEach(p => {
          L.marker(kmLL(p.km), {icon: L.divIcon({className:'', iconSize:null, html:'<div class="tpm" style="background:' + (p.passed ? (COLOR[p.type] || '#101010') : '#9CA3AF') + '">' + (p.passed ? '✓ ' : '') + esc(p.code) + '</div>'})})
            .addTo(tpLayer).bindPopup(esc(p.name) + (p.passed ? '<br>Lewat ' + esc(p.at) : '<br>Belum lewat'));
        });
        done.setLatLngs(data.status === 'not_started' ? [] : pathTo(posKm));
        if (runner) map.removeLayer(runner);
        if (data.status !== 'not_started' && data.status !== 'finished') {
          runner = L.marker(kmLL(posKm), {icon: L.divIcon({className:'', iconSize:null, html:'<div class="rn"></div>'}), zIndexOffset:1000})
            .addTo(map).bindTooltip(data.estimate ? 'Perkiraan posisi · KM ' + fmtKm(posKm) : (data.status === 'finished' ? 'Finish' : 'Posisi terakhir'), {direction:'top', offset:[0, -12]});
        }
      }
    }
    render();

    // refresh otomatis (berhenti saat tab tidak terlihat)
    setInterval(() => {
      if (document.hidden) return;
      fetch(window.__trkUrl, {headers:{'Accept':'application/json'}}).then(r => r.ok ? r.json() : null).then(d => { if (d) { data = d; render(); } }).catch(() => {});
    }, 30000);

    // bagikan
    document.querySelector('[data-trk-share]').addEventListener('click', e => {
      const url = location.href, title = 'Lacak ' + data.name + ' di Gerung Trail Run 2026';
      if (navigator.share) { navigator.share({title, url}).catch(() => {}); return; }
      navigator.clipboard && navigator.clipboard.writeText(url);
      e.currentTarget.textContent = '✓ Link disalin';
    });
  })();
</script>
@endverbatim
@endpush
