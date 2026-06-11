@extends('layouts.gtr')

@section('title', $cat->name . ' (' . $cat->distance . ') — Gerung Trail Run 2026')

@push('styles')
@if($cat->route_points)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
  .map-wrap{position:relative}
  #course-map{width:100%;height:360px;border-radius:14px;overflow:hidden;border:1px solid #E6E6E6;background:#eee}
  .leaflet-container{font-family:'Plus Jakarta Sans',sans-serif}
  .leaflet-tooltip.ws-tip{background:#101010;color:#fff;border:none;font-weight:700;font-size:11px;border-radius:6px}
  .leaflet-tooltip.ws-tip::before{border-top-color:#101010}
  .ws-divicon{background:none;border:none;filter:drop-shadow(0 2px 5px rgba(0,0,0,.45));cursor:pointer}
  .leaflet-popup.ws-popup .leaflet-popup-content-wrapper{background:#101010;color:#fff;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.45)}
  .leaflet-popup.ws-popup .leaflet-popup-content{margin:8px 13px;font-family:'Archivo',sans-serif;font-weight:700;font-size:12px}
  .leaflet-popup.ws-popup .leaflet-popup-tip{background:#101010}
  /* elevation profile */
  .elev-profile{margin-top:18px}
  .elev-head{display:flex;justify-content:space-between;align-items:baseline;margin-bottom:8px}
  .elev-title{font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#101010}
  .elev-meta{font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;color:#8A8A8A}
  .elev-chart{position:relative;cursor:crosshair}
  .elev-svg{width:100%;height:170px;display:block;border-bottom:1px solid #E6E6E6}
  .elev-cursor{position:absolute;top:0;bottom:0;width:0;border-left:1.5px solid #101010;display:none;pointer-events:none;transform:translateX(-50%);z-index:2}
  .elev-cursor .cdot{position:absolute;left:50%;top:0;transform:translate(-50%,-50%);width:11px;height:11px;border-radius:50%;background:#101010;border:2px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.45)}
  .elev-tip{position:absolute;top:-6px;transform:translate(-50%,-100%);background:#101010;color:#fff;font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;padding:4px 9px;border-radius:7px;white-space:nowrap;display:none;pointer-events:none;z-index:4}
  .elev-ymax,.elev-ymin{position:absolute;left:6px;font-family:'Archivo',sans-serif;font-size:10.5px;font-weight:700;color:#8A8A8A;background:rgba(255,255,255,.72);padding:1px 5px;border-radius:5px}
  .elev-ymax{top:4px}
  .elev-ymin{bottom:6px}
  .elev-markers{position:absolute;inset:0;pointer-events:none}
  .ws-mark{position:absolute;top:0;bottom:0;transform:translateX(-50%)}
  .ws-mark::before{content:'';position:absolute;top:14px;bottom:0;left:50%;width:0;border-left:1.5px dashed rgba(46,166,230,.6)}
  .ws-pin{position:absolute;top:-3px;left:50%;transform:translateX(-50%);width:18px;height:18px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.35))}
  .ws-pin svg{width:100%;height:100%;display:block}
  .elev-axis{display:flex;justify-content:space-between;font-family:'Archivo',sans-serif;font-size:11px;color:#9A9A9A;margin-top:6px}
  .ws-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
  .ws-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:999px;background:#F6F6F6;border:1px solid #ECECEC;font-size:12.5px;color:#101010;font-weight:600}
  .ws-chip svg{width:15px;height:15px;flex-shrink:0}

  /* Desktop: dock elevation profile to map bottom-right */
  @media (min-width:820px){
    #course-map{height:500px}
    .elev-profile{
      position:absolute;right:14px;bottom:14px;width:min(440px,48%);margin:0;z-index:600;
      background:rgba(255,255,255,.94);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);
      border:1px solid rgba(0,0,0,.08);border-radius:14px;box-shadow:0 16px 44px rgba(0,0,0,.32);
      padding:12px 14px 10px;
    }
    .elev-profile .elev-svg{height:120px;border-bottom-color:rgba(0,0,0,.08)}
    .elev-profile .ws-pin{width:15px;height:15px}
  }
</style>
@endif
@endpush

@section('content')

<!-- HEADER: FULL PHOTO -->
<header class="cat-hero" style="background-image:url('{{ $cat->header_url }}')">
  <div class="cat-hero-inner">
    <a class="back" href="{{ route('gtr') }}#category">← Semua Kategori</a>
    <span class="ch-tag"><span class="dot" style="background:{{ $cat->color }}"></span>{{ $cat->tag }}</span>
    <h1>{{ $cat->distance }}</h1>
    <div class="ch-name">{{ $cat->name }}</div>
  </div>
</header>

<!-- INFO STATS -->
<section class="detail-section">
  <div class="detail-wrap">
    <div class="cat-stats">
      <div class="cat-stat">
        <div class="lab">Reg Fee</div>
        <div class="val">{{ $cat->early_bird_formatted }} <span class="strike">{{ $cat->normal_formatted }}</span></div>
        <div class="sub">Early Bird{{ $cat->early_bird_until ? ' s/d ' . $cat->early_bird_until->translatedFormat('d M Y') : '' }}</div>
      </div>
      <div class="cat-stat">
        <div class="lab">Start</div>
        <div class="val">{{ $cat->start_time ?: '-' }}</div>
        <div class="sub">29 November 2026</div>
      </div>
      <div class="cat-stat">
        <div class="lab">Elevation Gain</div>
        <div class="val">{{ $cat->elevation_gain ?: '-' }}</div>
      </div>
      <div class="cat-stat">
        <div class="lab">Cut-Off Time</div>
        <div class="val">{{ $cat->cut_off_time ?: '-' }}</div>
      </div>
      <div class="cat-stat">
        <div class="lab">Water Station</div>
        <div class="val">{{ $cat->water_station_label }}</div>
      </div>
      <div class="cat-stat">
        <div class="lab">Award & Prize</div>
        <div class="val" style="font-size:15px;line-height:1.35">{{ $cat->award_prize ?: '-' }}</div>
      </div>
    </div>

    <!-- TABS -->
    <div class="tabs" id="cat-tabs">
      <div class="tab-btns" role="tablist">
        <button class="tab-btn active" data-tab="map" type="button">Course Map</button>
        <button class="tab-btn" data-tab="rundown" type="button">Event Rundown</button>
        <button class="tab-btn" data-tab="gear" type="button">Mandatory Gear</button>
        <button class="tab-btn" data-tab="gpx" type="button">GPX File</button>
      </div>

      <div class="tab-panel active" data-panel="map">
        @if($cat->route_points)
          @php
            $wsAll = collect($cat->water_stations ?? [])
              ->filter(fn ($w) => isset($w['km']) && $w['km'] !== '')
              ->map(fn ($w) => ['km' => (float) $w['km'], 'name' => $w['name'] ?? null])
              ->sortBy('km')->values();

            $ep = $cat->elevation_profile ?? [];
            $elev = null;
            if (count($ep) > 1) {
              $kms = array_map(fn ($p) => (float) $p[0], $ep);
              $eles = array_map(fn ($p) => (float) $p[1], $ep);
              $minKm = min($kms); $maxKm = max($kms);
              $minEle = min($eles); $maxEle = max($eles);
              $spanKm = max(0.001, $maxKm - $minKm);
              $spanEle = max(1, $maxEle - $minEle);
              $W = 1000; $H = 220; $padT = 16; $padB = 22;
              $fx = fn ($km) => ($km - $minKm) / $spanKm * $W;
              $fy = fn ($e) => $H - $padB - (($e - $minEle) / $spanEle) * ($H - $padT - $padB);
              $pts = [];
              foreach ($ep as $p) { $pts[] = round($fx((float) $p[0]), 1) . ',' . round($fy((float) $p[1]), 1); }
              $line = 'M ' . implode(' L ', $pts);
              $area = $line . ' L ' . $W . ',' . ($H - $padB) . ' L 0,' . ($H - $padB) . ' Z';
              $elev = compact('kms', 'eles', 'minKm', 'maxKm', 'minEle', 'maxEle', 'line', 'area');
            }
            $drop = '<svg viewBox="0 0 24 24" fill="#2EA6E6" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3c0 0-6 6.6-6 11a6 6 0 0 0 12 0c0-4.4-6-11-6-11z"/></svg>';
          @endphp

          <div class="map-wrap">
          <div id="course-map"
               data-points='@json($cat->route_points)'
               data-color="{{ $cat->color }}"
               data-ws='@json($wsAll)'></div>

          @if($elev)
          <div class="elev-profile">
            <div class="elev-head">
              <span class="elev-title">Profil Elevasi</span>
              <span class="elev-meta">{{ $cat->elevation_gain }} · {{ rtrim(rtrim(number_format($elev['maxKm'], 1), '0'), '.') }} km</span>
            </div>
            <div class="elev-chart"
                 data-ep='@json($cat->elevation_profile)'
                 data-minkm="{{ $elev['minKm'] }}" data-maxkm="{{ $elev['maxKm'] }}"
                 data-minele="{{ $elev['minEle'] }}" data-maxele="{{ $elev['maxEle'] }}">
              <svg class="elev-svg" viewBox="0 0 1000 220" preserveAspectRatio="none">
                <defs>
                  <linearGradient id="elevGrad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0" stop-color="{{ $cat->color }}" stop-opacity=".35"/>
                    <stop offset="1" stop-color="{{ $cat->color }}" stop-opacity="0"/>
                  </linearGradient>
                </defs>
                <path d="{{ $elev['area'] }}" fill="url(#elevGrad)"/>
                <path d="{{ $elev['line'] }}" fill="none" stroke="{{ $cat->color }}" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
                @foreach($wsAll as $w)
                  @php $wx = round(($w['km'] - $elev['minKm']) / max(0.001, $elev['maxKm'] - $elev['minKm']) * 1000, 1); @endphp
                  <line x1="{{ $wx }}" y1="0" x2="{{ $wx }}" y2="220" stroke="#2EA6E6" stroke-width="1" stroke-dasharray="5 5" vector-effect="non-scaling-stroke" opacity=".6"/>
                @endforeach
              </svg>
              <span class="elev-ymax">{{ round($elev['maxEle']) }} m</span>
              <span class="elev-ymin">{{ round($elev['minEle']) }} m</span>
              <div class="elev-markers">
                @foreach($wsAll as $w)
                  @php $pct = ($w['km'] - $elev['minKm']) / max(0.001, $elev['maxKm'] - $elev['minKm']) * 100; @endphp
                  <div class="ws-mark" style="left:{{ round(min(100, max(0, $pct)), 2) }}%" title="{{ $w['name'] ?: 'Water Station' }} · KM {{ $w['km'] }}"><span class="ws-pin">{!! $drop !!}</span></div>
                @endforeach
              </div>
              <div class="elev-cursor"><span class="cdot"></span></div>
              <div class="elev-tip"></div>
            </div>
            <div class="elev-axis"><span>0 km</span><span>{{ rtrim(rtrim(number_format($elev['maxKm'], 1), '0'), '.') }} km</span></div>
          </div>
          @endif
          </div>

          @if($wsAll->count())
          <div class="ws-list">
            @foreach($wsAll as $i => $w)
              <span class="ws-chip">{!! $drop !!}{{ $w['name'] ?: 'WS ' . ($i + 1) }} · KM {{ rtrim(rtrim(number_format($w['km'], 1), '0'), '.') }}</span>
            @endforeach
          </div>
          @endif

          <p class="tab-note">Rute {{ $cat->distance }} digambar dari file GPX resmi.</p>
        @else
          <img class="tab-img" src="{{ $cat->header_url }}" alt="Course map {{ $cat->distance }}">
          <p class="tab-note">Peta rute {{ $cat->distance }} akan tampil di sini setelah file GPX diunggah.</p>
        @endif
      </div>

      <div class="tab-panel" data-panel="rundown">
        @if(!empty($cat->rundown))
          <div class="rundown">
            @foreach($cat->rundown as $row)
              <div class="rundown-row">
                <span class="t">
                  @if(!empty($row['date']))<span class="d">{{ $row['date'] }}</span>@endif
                  <span class="hh">{{ $row['time'] ?? '' }}</span>
                </span>
                <span class="a">
                  <span class="act">{{ $row['activity'] ?? '' }}</span>
                  @if(!empty($row['location']))<span class="loc">📍 {{ $row['location'] }}</span>@endif
                </span>
              </div>
            @endforeach
          </div>
        @else
          <p class="tab-note">Rundown acara akan diumumkan menjelang race day.</p>
        @endif
      </div>

      <div class="tab-panel" data-panel="gear">
        @if(!empty($cat->mandatory_gear))
          <div class="gear-list">
            @foreach($cat->mandatory_gear as $item)
              <div class="gear-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span>{{ $item }}</span>
              </div>
            @endforeach
          </div>
        @else
          <p class="tab-note">Daftar mandatory gear akan diumumkan menjelang race day.</p>
        @endif
      </div>

      <div class="tab-panel" data-panel="gpx">
        <div class="gpx-box">
          <p>File GPX rute <strong>{{ $cat->name }} ({{ $cat->distance }})</strong> bisa diunduh untuk dipakai di GPS / smartwatch.</p>
          @if($cat->gpx_url)
            <a class="btn-primary" href="{{ $cat->gpx_url }}" download>Download GPX <span class="arr">↓</span></a>
          @else
            <span class="tab-note">File GPX belum tersedia — segera diunggah panitia.</span>
          @endif
        </div>
      </div>
    </div>

    <div style="margin-top:40px">
      <a class="btn-primary" href="{{ auth('runner')->check() ? route('gtr.dashboard') : route('gtr.login') }}">Register Now <span class="arr">→</span></a>
    </div>
  </div>
</section>

@push('scripts')
@if($cat->route_points)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endif
@verbatim
<script>
  (function(){
    const root = document.getElementById('cat-tabs');
    if(root){
      const btns = root.querySelectorAll('.tab-btn');
      const panels = root.querySelectorAll('.tab-panel');
      btns.forEach(btn => btn.addEventListener('click', () => {
        const key = btn.dataset.tab;
        btns.forEach(b => b.classList.toggle('active', b === btn));
        panels.forEach(p => p.classList.toggle('active', p.dataset.panel === key));
        if(key === 'map' && window._gtrMap){ setTimeout(() => window._gtrMap.invalidateSize(), 80); }
      }));
    }

    const el = document.getElementById('course-map');
    if(el && window.L){
      let pts = [];
      try { pts = JSON.parse(el.dataset.points || '[]'); } catch(e){}
      if(pts.length){
        const map = L.map(el, {scrollWheelZoom:false, attributionControl:false});
        window._gtrMap = map;
        const baseLayers = {
          'Light': L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {maxZoom:19, subdomains:'abcd'}),
          'Street': L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19}),
          'Satelit': L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom:19}),
          'Terrain': L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {maxZoom:17})
        };
        baseLayers['Light'].addTo(map);
        L.control.layers(baseLayers, null, {position:'topright', collapsed:false}).addTo(map);
        const line = L.polyline(pts, {color: el.dataset.color || '#E53935', weight:4, opacity:.95}).addTo(map);
        map.fitBounds(line.getBounds(), {padding:[28,28]});
        L.circleMarker(pts[0], {radius:6, color:'#fff', weight:2, fillColor:'#22c55e', fillOpacity:1}).addTo(map).bindTooltip('Start', {direction:'top', className:'ws-tip'});
        L.circleMarker(pts[pts.length-1], {radius:6, color:'#fff', weight:2, fillColor:'#E53935', fillOpacity:1}).addTo(map).bindTooltip('Finish', {direction:'top', className:'ws-tip'});

        // cumulative distance (km) along the route
        const toRad = d => d * Math.PI / 180, R = 6371;
        const cum = [0];
        for(let i=1;i<pts.length;i++){
          const a1=pts[i-1], a2=pts[i];
          const dLa=toRad(a2[0]-a1[0]), dLo=toRad(a2[1]-a1[1]);
          const h=Math.sin(dLa/2)**2 + Math.cos(toRad(a1[0]))*Math.cos(toRad(a2[0]))*Math.sin(dLo/2)**2;
          cum[i]=cum[i-1] + 2*R*Math.asin(Math.min(1, Math.sqrt(h)));
        }
        const total = cum[cum.length-1] || 1;
        function kmToLatLng(km){
          km = Math.max(0, Math.min(total, km));
          let j=1; while(j < cum.length && cum[j] < km) j++;
          if(j >= cum.length) j = cum.length - 1;
          const seg = cum[j]-cum[j-1];
          const t = seg ? (km - cum[j-1]) / seg : 0;
          return [pts[j-1][0] + (pts[j][0]-pts[j-1][0])*t, pts[j-1][1] + (pts[j][1]-pts[j-1][1])*t];
        }

        // Water stations — droplet icon + permanent name label
        let ws = [];
        try { ws = JSON.parse(el.dataset.ws || '[]'); } catch(e){}
        const dropHtml = '<svg viewBox="0 0 24 24" width="24" height="24" fill="#2EA6E6" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3c0 0-6 6.6-6 11a6 6 0 0 0 12 0c0-4.4-6-11-6-11z"/></svg>';
        ws.forEach((w, idx) => {
          const icon = L.divIcon({html: dropHtml, className:'ws-divicon', iconSize:[24,24], iconAnchor:[12,22]});
          L.marker(kmToLatLng(Number(w.km)), {icon}).addTo(map)
            .bindPopup((w.name || ('WS ' + (idx+1))) + ' · KM ' + w.km, {className:'ws-popup', closeButton:false, offset:[0,-14]});
        });

        // ---- Elevation profile <-> map scrubber ----
        const chart = document.querySelector('.elev-chart');
        if(chart){
          let ep = [];
          try { ep = JSON.parse(chart.dataset.ep || '[]'); } catch(e){}
          const maxKm = parseFloat(chart.dataset.maxkm) || total;
          const minKm = parseFloat(chart.dataset.minkm) || 0;
          const minEle = parseFloat(chart.dataset.minele) || 0;
          const maxEle = parseFloat(chart.dataset.maxele) || (minEle + 1);
          const cursor = chart.querySelector('.elev-cursor');
          const cdot = chart.querySelector('.cdot');
          const tip = chart.querySelector('.elev-tip');
          const H = 220, padT = 16, padB = 22; // must match the SVG viewBox
          const hover = L.circleMarker(pts[0], {radius:7, color:'#fff', weight:3, fillColor:'#101010', fillOpacity:1});
          function eleAtKm(km){
            if(!ep.length) return minEle;
            for(let i=1;i<ep.length;i++){
              if(ep[i][0] >= km){
                const k0=ep[i-1][0], k1=ep[i][0], e0=ep[i-1][1], e1=ep[i][1];
                const t=(k1-k0)?(km-k0)/(k1-k0):0;
                return e0 + (e1-e0)*t;
              }
            }
            return ep[ep.length-1][1];
          }
          function move(clientX){
            const rect = chart.getBoundingClientRect();
            const frac = Math.min(1, Math.max(0, (clientX-rect.left)/rect.width));
            const km = minKm + frac*(maxKm-minKm);
            const ele = eleAtKm(km);
            const vy = H - padB - ((ele-minEle)/Math.max(1,(maxEle-minEle)))*(H-padT-padB);
            const topPct = vy/H*100;
            cursor.style.left = (frac*100)+'%'; cursor.style.display='block';
            cdot.style.top = topPct+'%';
            tip.style.left = (frac*100)+'%'; tip.style.top = topPct+'%'; tip.style.display='block';
            tip.textContent = 'KM '+km.toFixed(1)+' · '+Math.round(ele)+' m';
            hover.setLatLng(kmToLatLng(km));
            if(!map.hasLayer(hover)) hover.addTo(map);
          }
          function hide(){ cursor.style.display='none'; tip.style.display='none'; if(map.hasLayer(hover)) map.removeLayer(hover); }
          chart.addEventListener('mousemove', e => move(e.clientX));
          chart.addEventListener('mouseleave', hide);
          chart.addEventListener('touchmove', e => { if(e.touches[0]) move(e.touches[0].clientX); }, {passive:true});
        }
      }
    }
  })();
</script>
@endverbatim
@endpush

@endsection
