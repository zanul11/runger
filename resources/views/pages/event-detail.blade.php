@extends('layouts.app')
@section('title', $event->title . ' — ' . $event->date->format('d M Y'))

@push('head')
@if($route)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@endif
@endpush

@push('styles')
<style>
  /* ===== MOBILE-FIRST BASE ===== */
  body{padding-bottom:78px}

  /* Route section */
  .rt-section{margin-top:32px;padding-top:28px;border-top:1px solid var(--line)}
  .rt-eye{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--volt);font-weight:600;margin-bottom:8px;display:inline-flex;align-items:center;gap:10px}
  .rt-eye::before{content:'';width:20px;height:1px;background:var(--volt)}
  .rt-title{font-family:'Bebas Neue',sans-serif;font-size:30px;line-height:.95;color:#fff;margin-bottom:6px}
  .rt-title em{font-style:normal;color:var(--volt)}
  .rt-sub{font-size:13px;opacity:.7;line-height:1.5;margin-bottom:14px}
  .rt-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:16px}
  .rt-stat{background:rgba(255,255,255,.03);border:1px solid var(--line);border-radius:4px;padding:12px 10px;text-align:center}
  .rt-stat .v{font-family:'Bebas Neue',sans-serif;font-size:22px;line-height:.95;color:var(--volt);letter-spacing:0}
  .rt-stat .v small{font-family:'Inter',sans-serif;font-size:11px;font-weight:500;color:#fff;opacity:.65;margin-left:2px}
  .rt-stat .l{font-family:'JetBrains Mono',monospace;font-size:8.5px;letter-spacing:.14em;text-transform:uppercase;opacity:.55;margin-top:4px}

  .map-frame{position:relative;background:#0a0a0a;border:1px solid var(--line);border-radius:4px;overflow:hidden;height:340px;margin-bottom:14px}
  #route-map{position:absolute;inset:0;width:100%;height:100%;z-index:1}
  .map-legend{position:absolute;bottom:10px;left:10px;z-index:5;background:rgba(0,0,0,.78);backdrop-filter:blur(8px);border:1px solid var(--line-strong);padding:7px 10px;font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:#fff}
  .map-legend .leg-row{display:flex;align-items:center;gap:6px;margin:3px 0;opacity:.85}
  .leg-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
  .leg-dot.start{background:#22c55e;box-shadow:0 0 0 2px rgba(34,197,94,.25)}
  .leg-dot.km{background:var(--volt);box-shadow:0 0 0 2px rgba(226,240,84,.25)}
  .leg-dot.route{background:transparent;border:2px solid #4a7fff;width:7px;height:7px}
  .map-layers{position:absolute;top:10px;right:10px;z-index:5;background:rgba(0,0,0,.78);backdrop-filter:blur(8px);border:1px solid var(--line-strong);padding:3px;display:flex;gap:2px}
  .map-layer-btn{background:transparent;border:none;color:#fff;cursor:pointer;font-family:'JetBrains Mono',monospace;font-size:9px;letter-spacing:.12em;text-transform:uppercase;padding:5px 7px;opacity:.55}
  .map-layer-btn:hover{opacity:1}
  .map-layer-btn.active{background:var(--volt);color:var(--ink);opacity:1;font-weight:600}

  .leaflet-container{background:#0a0a0a !important;font-family:'JetBrains Mono',monospace !important}
  .leaflet-popup-content-wrapper{background:var(--ink) !important;color:var(--bone) !important;border-radius:2px !important;border:1px solid var(--line-strong)}
  .leaflet-popup-tip{background:var(--ink) !important}
  .leaflet-popup-content{font-family:'JetBrains Mono',monospace !important;font-size:11px !important}
  .leaflet-popup-content b{color:var(--volt)}
  .km-marker{width:26px;height:26px;border-radius:50%;background:var(--volt);color:var(--ink);display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;border:2px solid #000;box-shadow:0 2px 10px rgba(0,0,0,.5),0 0 0 3px rgba(226,240,84,.18)}
  .start-marker{width:30px;height:30px;border-radius:50%;background:#22c55e;color:#fff;display:flex;align-items:center;justify-content:center;font-family:'JetBrains Mono',monospace;font-size:10px;font-weight:700;border:2px solid #000;box-shadow:0 2px 14px rgba(34,197,94,.5),0 0 0 4px rgba(34,197,94,.18)}

  /* Elevation profile */
  .elev-wrap{background:rgba(255,255,255,.03);border:1px solid var(--line);border-radius:4px;padding:14px;margin-bottom:14px}
  .elev-head{display:flex;justify-content:space-between;align-items:baseline;gap:10px;margin-bottom:10px;flex-wrap:wrap}
  .elev-head .t{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;color:var(--volt);font-weight:600}
  .elev-head .meta{font-family:'JetBrains Mono',monospace;font-size:10px;opacity:.6;letter-spacing:.06em}
  .elev-svg{display:block;width:100%;height:140px}
  .elev-axis{font-family:'JetBrains Mono',monospace;font-size:8.5px;fill:rgba(255,255,255,.5);letter-spacing:.08em}

  /* GPX download */
  .gpx-bar{display:flex;flex-direction:column;align-items:stretch;gap:12px;padding:14px;background:rgba(255,255,255,.03);border:1px solid var(--line);border-radius:4px}
  .gpx-info{display:flex;align-items:center;gap:12px;min-width:0}
  .gpx-ic{width:38px;height:38px;flex-shrink:0;border-radius:8px;background:rgba(226,240,84,.1);border:1px solid rgba(226,240,84,.3);display:flex;align-items:center;justify-content:center;color:var(--volt)}
  .gpx-ic svg{width:17px;height:17px}
  .gpx-text{min-width:0;flex:1}
  .gpx-text .t{font-size:13px;color:#fff;font-weight:600;line-height:1.3}
  .gpx-text .s{font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.1em;opacity:.6;margin-top:3px;text-transform:uppercase}
  .gpx-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;padding:12px 16px;border-radius:3px;background:var(--volt);color:var(--ink);font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.16em;text-transform:uppercase;font-weight:700;transition:transform .15s}
  .gpx-btn:hover{transform:translateY(-1px)}
  .gpx-btn svg{width:13px;height:13px}

  .ph{padding:24px 16px 0}
  .ph-inner{max-width:1180px;margin:0 auto}
  .back-link{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.16em;text-transform:uppercase;opacity:.6;transition:opacity .15s, color .15s;display:inline-flex;align-items:center;gap:6px}
  .back-link:hover{opacity:1;color:var(--volt)}

  .ev-section{padding:24px 16px}
  .ev-wrap{max-width:1180px;margin:0 auto}

  .ev-poster-wrap{
    position:relative;background:var(--runger-blue-deep);overflow:hidden;
    border-radius:6px;border:1px solid var(--line-strong);
    aspect-ratio:4/5;max-width:480px;margin:0 auto;
  }
  .ev-poster-wrap img{width:100%;height:100%;object-fit:cover;display:block}
  .ev-poster-wrap.placeholder{
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg, var(--runger-blue), var(--runger-blue-deep));
    color:#fff;font-family:'Bebas Neue',sans-serif;font-size:64px;letter-spacing:.02em;
  }

  .ev-status-pill{
    display:inline-flex;align-items:center;gap:8px;padding:6px 12px;border-radius:999px;
    font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.18em;
    text-transform:uppercase;font-weight:600;margin-bottom:14px;
  }
  .ev-status-pill .dot{width:6px;height:6px;border-radius:50%;animation:pulse 1.6s infinite}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}
  .ev-status-pill.upcoming{background:rgba(226,240,84,.12);color:var(--volt);border:1px solid rgba(226,240,84,.35)}
  .ev-status-pill.upcoming .dot{background:var(--volt)}
  .ev-status-pill.coming_soon{background:rgba(255,255,255,.05);color:#fff;border:1px solid var(--line-strong)}
  .ev-status-pill.coming_soon .dot{background:#fff;opacity:.6}
  .ev-status-pill.completed{background:rgba(255,255,255,.04);color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.15)}
  .ev-status-pill.completed .dot{background:rgba(255,255,255,.4);animation:none}

  .ev-title{font-family:'Bebas Neue',sans-serif;font-size:44px;line-height:.92;letter-spacing:-.005em;color:#fff;margin-bottom:6px}
  .ev-title em{font-style:normal;color:var(--volt)}
  .ev-sub{font-family:'JetBrains Mono',monospace;font-size:12px;letter-spacing:.06em;opacity:.7;margin-bottom:18px}
  .ev-tag{display:inline-block;font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.16em;text-transform:uppercase;background:rgba(226,240,84,.12);color:var(--volt);border:1px solid rgba(226,240,84,.3);padding:5px 10px;margin-bottom:18px}

  /* Info rows */
  .info-rows{display:grid;gap:0;border:1px solid var(--line);border-radius:4px;background:rgba(0,0,0,.25);overflow:hidden;margin-top:18px}
  .info-row{display:flex;align-items:flex-start;gap:12px;padding:14px;border-bottom:1px solid var(--line)}
  .info-row:last-child{border-bottom:none}
  .info-ic{width:36px;height:36px;flex-shrink:0;background:rgba(226,240,84,.1);color:var(--volt);border:1px solid rgba(226,240,84,.25);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px}
  .info-content{flex:1;min-width:0}
  .info-lbl{font-family:'JetBrains Mono',monospace;font-size:9.5px;letter-spacing:.18em;text-transform:uppercase;opacity:.55;margin-bottom:3px}
  .info-val{font-size:14px;color:#fff;font-weight:500;line-height:1.35;word-wrap:break-word}
  .info-val small{display:block;font-size:11.5px;font-weight:400;opacity:.7;margin-top:2px}

  .ev-note{margin-top:18px;padding:14px 16px;border:1px solid var(--line);border-radius:4px;background:rgba(255,255,255,.02);font-size:14px;line-height:1.6;opacity:.85}

  /* Countdown */
  .countdown{margin:24px 0 0;padding:18px 16px;background:#000;border:1px solid var(--line);border-radius:4px}
  .cd-eye{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--volt);display:flex;align-items:center;gap:10px;margin-bottom:12px}
  .cd-eye::before{content:'';width:20px;height:1px;background:var(--volt)}
  .cd-blocks{display:grid;grid-template-columns:repeat(4,1fr);gap:6px}
  .cd-block{text-align:center;padding:8px 4px;border:1px solid var(--line);border-radius:3px;background:rgba(255,255,255,.02)}
  .cd-num{font-family:'Bebas Neue',sans-serif;font-size:30px;line-height:.95;color:#fff}
  .cd-lab{font-family:'JetBrains Mono',monospace;font-size:8.5px;letter-spacing:.16em;text-transform:uppercase;opacity:.55;margin-top:2px}

  /* Inline CTA buttons (desktop) */
  .ev-actions{display:none;gap:10px;margin-top:18px;flex-wrap:wrap}
  .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 18px;border-radius:3px;font-family:'Inter',sans-serif;font-weight:600;font-size:12.5px;letter-spacing:.06em;text-transform:uppercase;transition:transform .15s, background .2s;flex:1;min-width:0}
  .btn-p{background:var(--volt);color:var(--ink)}
  .btn-p:hover{transform:translateY(-1px)}
  .btn-g{background:rgba(255,255,255,.04);color:#fff;border:1px solid var(--line-strong)}
  .btn-g:hover{background:rgba(255,255,255,.1)}

  /* Sponsors section */
  .sp-section{margin-top:32px;padding:28px 0;border-top:1px solid var(--line)}
  .sp-eye{font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.22em;text-transform:uppercase;color:var(--volt);font-weight:600;margin-bottom:8px;display:inline-flex;align-items:center;gap:10px}
  .sp-eye::before{content:'';width:20px;height:1px;background:var(--volt)}
  .sp-title{font-family:'Bebas Neue',sans-serif;font-size:30px;line-height:.95;color:#fff;margin-bottom:6px}
  .sp-title em{font-style:normal;color:var(--volt)}
  .sp-sub{font-size:13px;opacity:.7;line-height:1.5;margin-bottom:18px}

  .sp-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  .sp-card{background:#fff;border-radius:8px;padding:14px;display:flex;align-items:center;justify-content:center;min-height:90px;position:relative;transition:transform .15s;border:1px solid var(--line-strong)}
  .sp-card:hover{transform:translateY(-2px)}
  .sp-card img{max-height:50px;width:auto;object-fit:contain}
  .sp-card .sp-name-fallback{font-family:'Bebas Neue',sans-serif;font-size:22px;color:var(--runger-blue);letter-spacing:.02em;text-align:center}
  .sp-card .sp-tier{position:absolute;top:6px;right:8px;font-family:'JetBrains Mono',monospace;font-size:8.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--ink);opacity:.5;font-weight:600}

  /* Sticky mobile CTA bar */
  .mobile-bar{display:flex;position:fixed;left:0;right:0;bottom:0;z-index:60;padding:10px 12px;background:rgba(0,0,0,.94);backdrop-filter:blur(12px);border-top:1px solid var(--line-strong);gap:8px;padding-bottom:max(10px, env(safe-area-inset-bottom))}
  .mobile-bar .btn{padding:13px 10px;font-size:11.5px;flex:1;width:auto;min-width:0}

  /* ===== DESKTOP ===== */
  @media (min-width:820px){
    body{padding-bottom:0}
    .ph{padding:36px 32px 0}
    .ev-section{padding:32px 32px 64px}
    .ev-grid{display:grid;grid-template-columns:340px 1fr;gap:36px;align-items:start}
    .ev-poster-wrap{margin:0;max-width:none;aspect-ratio:4/5}
    .ev-title{font-size:60px}
    .ev-actions{display:flex}
    .btn{flex:initial;min-width:160px}
    .countdown{padding:22px 24px}
    .cd-blocks{grid-template-columns:repeat(4,minmax(80px,1fr));gap:8px}
    .cd-num{font-size:42px}
    .sp-section{padding:48px 0}
    .sp-title{font-size:42px}
    .sp-grid{grid-template-columns:repeat(4,1fr);gap:16px}
    .sp-card{padding:20px;min-height:120px}
    .sp-card img{max-height:64px}
    .mobile-bar{display:none}
  }
</style>
@endpush

@section('body')
<x-site-nav active="agenda" />

<header class="ph">
  <div class="ph-inner">
    <a class="back-link" href="{{ route('agenda') }}">← Kembali ke agenda</a>
  </div>
</header>

<section class="ev-section">
  <div class="ev-wrap">
    <div class="ev-grid">
      {{-- Poster --}}
      @if($event->poster_image)
        <div class="ev-poster-wrap">
          <img src="{{ media_url($event->poster_image) }}" alt="{{ $event->title }}">
        </div>
      @else
        <div class="ev-poster-wrap placeholder">{{ $event->distance_text ?? '🏃' }}</div>
      @endif

      {{-- Info --}}
      <div>
        @php
          $statusLabel = match($event->status) {
            'upcoming' => 'Upcoming Event',
            'coming_soon' => 'Coming Soon',
            'completed' => 'Event Selesai',
            default => ucfirst($event->status),
          };
        @endphp
        <span class="ev-status-pill {{ $event->status }}"><span class="dot"></span>{{ $statusLabel }}</span>

        <h1 class="ev-title">{{ $event->title }}<em>.</em></h1>
        @if($event->subtitle)<div class="ev-sub">{{ $event->subtitle }}</div>@endif
        @if($event->tag)<div class="ev-tag">{{ $event->tag }}</div>@endif

        <div class="info-rows">
          <div class="info-row">
            <div class="info-ic">📅</div>
            <div class="info-content">
              <div class="info-lbl">Tanggal & Waktu</div>
              <div class="info-val">
                @if($event->is_coming_soon)
                  <strong style="color:var(--volt)">TBA</strong><small>Detail diumumkan menjelang event</small>
                @else
                  {{ $event->date->translatedFormat('l, d F Y') }} · <strong style="color:var(--volt)">{{ \Carbon\Carbon::parse($event->time)->format('H.i') }} WITA</strong>
                  @if($event->briefing)<small>{{ $event->briefing }}</small>@endif
                @endif
              </div>
            </div>
          </div>
          @if($event->tikum)
          <div class="info-row">
            <div class="info-ic">📍</div>
            <div class="info-content">
              <div class="info-lbl">Titik Kumpul</div>
              <div class="info-val">{{ $event->tikum }}@if($event->location)<small>{{ $event->location }}</small>@endif</div>
            </div>
          </div>
          @endif
          @if($event->distance_text)
          <div class="info-row">
            <div class="info-ic">🏃</div>
            <div class="info-content">
              <div class="info-lbl">Jarak & Pace</div>
              <div class="info-val">{{ $event->distance_text }}@if($event->pace)<small>{{ $event->pace }}</small>@endif</div>
            </div>
          </div>
          @endif
          @if($event->fee)
          <div class="info-row">
            <div class="info-ic">💸</div>
            <div class="info-content">
              <div class="info-lbl">Biaya</div>
              <div class="info-val">{{ $event->fee }}</div>
            </div>
          </div>
          @endif
        </div>

        @if($event->note)
          <div class="ev-note">{{ $event->note }}</div>
        @endif

        @if(!$event->is_coming_soon && $event->status !== 'completed')
          <div class="countdown">
            <div class="cd-eye" id="cd-eye">Mulai dalam</div>
            <div class="cd-blocks">
              <div class="cd-block"><div class="cd-num" id="cd-d">--</div><div class="cd-lab">Hari</div></div>
              <div class="cd-block"><div class="cd-num" id="cd-h">--</div><div class="cd-lab">Jam</div></div>
              <div class="cd-block"><div class="cd-num" id="cd-m">--</div><div class="cd-lab">Menit</div></div>
              <div class="cd-block"><div class="cd-num" id="cd-s">--</div><div class="cd-lab">Detik</div></div>
            </div>
          </div>
        @endif

        @if($event->cta_primary_href || $event->cta_ghost_href)
          <div class="ev-actions">
            @if($event->cta_primary_href)
              <a class="btn btn-p" href="{{ $event->cta_primary_href }}" target="_blank" rel="noopener">{{ $event->cta_primary_label ?? 'Konfirmasi Gabung' }} →</a>
            @endif
            @if($event->cta_ghost_href)
              <a class="btn btn-g" href="{{ $event->cta_ghost_href }}" target="_blank" rel="noopener">{{ $event->cta_ghost_label ?? 'Lebih Lanjut' }} →</a>
            @endif
          </div>
        @endif
      </div>
    </div>

    {{-- Route map + elevation profile --}}
    @if($route && $route->route_points && count($route->route_points))
      @php
        $profile = $route->elevation_profile ?? [];
        $eMin = $route->elevation_min_m ?? 0;
        $eMax = $route->elevation_max_m ?? 0;
        // Build SVG polyline for elevation profile
        $svgPath = '';
        if (count($profile) >= 2) {
            $w = 1000; $h = 140; $padTop = 16; $padBottom = 24; $padLeft = 8; $padRight = 8;
            $maxKm = max(array_column($profile, 0));
            $eSpan = max($eMax - $eMin, 1);
            $coords = [];
            foreach ($profile as $i => [$km, $ele]) {
                $x = $padLeft + ($km / $maxKm) * ($w - $padLeft - $padRight);
                $y = $padTop + (1 - ($ele - $eMin) / $eSpan) * ($h - $padTop - $padBottom);
                $coords[] = round($x, 1) . ',' . round($y, 1);
            }
            $svgPath = 'M' . implode(' L', $coords);
            // Fill path closes to baseline
            $fillPath = $svgPath . ' L' . round($padLeft + ($w - $padLeft - $padRight), 1) . ',' . ($h - $padBottom) . ' L' . $padLeft . ',' . ($h - $padBottom) . ' Z';
        }
      @endphp

      <section class="rt-section">
        <div class="rt-eye">Rute · {{ $route->total_km }} KM</div>
        <h2 class="rt-title">Peta <em>interaktif.</em></h2>
        <p class="rt-sub">Track GPS dengan marker tiap kilometer. Klik marker buat detail.</p>

        <div class="rt-stats">
          <div class="rt-stat"><div class="v">{{ $route->total_km }}<small>km</small></div><div class="l">Total</div></div>
          <div class="rt-stat"><div class="v">+{{ $route->elevation_gain_m }}<small>m</small></div><div class="l">Elev Gain</div></div>
          <div class="rt-stat"><div class="v">{{ $route->km_marker_count }}</div><div class="l">KM Markers</div></div>
        </div>

        <div class="map-frame">
          <div id="route-map"></div>
          <div class="map-layers">
            <button class="map-layer-btn" data-layer="dark" type="button">Dark</button>
            <button class="map-layer-btn active" data-layer="street" type="button">Street</button>
            <button class="map-layer-btn" data-layer="satellite" type="button">Sat</button>
            <button class="map-layer-btn" data-layer="terrain" type="button">Terra</button>
          </div>
          <div class="map-legend">
            <div class="leg-row"><span class="leg-dot start"></span> Start · Tikum</div>
            <div class="leg-row"><span class="leg-dot km"></span> KM 1–{{ $route->km_marker_count }}</div>
            <div class="leg-row"><span class="leg-dot route"></span> Track GPS</div>
          </div>
        </div>

        @if(count($profile) >= 2)
          <div class="elev-wrap">
            <div class="elev-head">
              <span class="t">Elevation Profile</span>
              <span class="meta">{{ $route->elevation_min_m }}m → {{ $route->elevation_max_m }}m · ∆{{ $route->elevation_max_m - $route->elevation_min_m }}m</span>
            </div>
            <svg class="elev-svg" viewBox="0 0 1000 140" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <linearGradient id="elev-grad" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" stop-color="oklch(0.88 0.18 110)" stop-opacity="0.55"/>
                  <stop offset="100%" stop-color="oklch(0.88 0.18 110)" stop-opacity="0.05"/>
                </linearGradient>
              </defs>
              {{-- baseline --}}
              <line x1="8" y1="116" x2="992" y2="116" stroke="rgba(255,255,255,.12)" stroke-width="1"/>
              {{-- gridlines (5 horizontal) --}}
              @for($i = 1; $i < 5; $i++)
                <line x1="8" y1="{{ 16 + $i * 20 }}" x2="992" y2="{{ 16 + $i * 20 }}" stroke="rgba(255,255,255,.04)" stroke-width="1"/>
              @endfor
              {{-- fill --}}
              <path d="{{ $fillPath }}" fill="url(#elev-grad)"/>
              {{-- line --}}
              <path d="{{ $svgPath }}" stroke="oklch(0.88 0.18 110)" stroke-width="1.8" fill="none" vector-effect="non-scaling-stroke"/>
              {{-- axis labels --}}
              <text x="8" y="135" class="elev-axis">0 km</text>
              <text x="992" y="135" text-anchor="end" class="elev-axis">{{ $route->total_km }} km</text>
              <text x="8" y="12" class="elev-axis">{{ $route->elevation_max_m }} m</text>
              <text x="8" y="115" class="elev-axis">{{ $route->elevation_min_m }} m</text>
            </svg>
          </div>
        @endif

        @if($route->gpx_file)
          <div class="gpx-bar">
            <div class="gpx-info">
              <div class="gpx-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              </div>
              <div class="gpx-text">
                <div class="t">Download File GPX</div>
                <div class="s">{{ $route->total_km }} km · +{{ $route->elevation_gain_m }}m · Strava / COROS / Garmin</div>
              </div>
            </div>
            <a class="gpx-btn" href="{{ media_url($route->gpx_file) }}" download>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Download GPX
            </a>
          </div>
        @endif
      </section>
    @endif

    {{-- Sponsors --}}
    @if($sponsors->count())
      <section class="sp-section">
        <div class="sp-eye">Sponsors · {{ $sponsors->count() }}</div>
        <h2 class="sp-title">Didukung <em>oleh.</em></h2>
        <p class="sp-sub">Terima kasih kepada partner & sponsor yang mendukung event ini.</p>
        <div class="sp-grid">
          @foreach($sponsors as $sp)
            @php $card = '<span class="sp-tier">' . strtoupper($sp->tier) . '</span>'; @endphp
            @if($sp->link)
              <a class="sp-card" href="{{ $sp->link }}" target="_blank" rel="noopener" title="{{ $sp->name }}">
                {!! $card !!}
                @if($sp->logo)
                  <img src="{{ media_url($sp->logo) }}" alt="{{ $sp->name }}">
                @else
                  <span class="sp-name-fallback">{{ $sp->name }}</span>
                @endif
              </a>
            @else
              <div class="sp-card" title="{{ $sp->name }}">
                {!! $card !!}
                @if($sp->logo)
                  <img src="{{ media_url($sp->logo) }}" alt="{{ $sp->name }}">
                @else
                  <span class="sp-name-fallback">{{ $sp->name }}</span>
                @endif
              </div>
            @endif
          @endforeach
        </div>
      </section>
    @endif
  </div>
</section>

<x-site-footer />

@if($event->cta_primary_href || $event->cta_ghost_href)
<div class="mobile-bar">
  @if($event->cta_primary_href)
    <a class="btn btn-p" href="{{ $event->cta_primary_href }}" target="_blank" rel="noopener">{{ $event->cta_primary_label ?? 'Gabung' }}</a>
  @endif
  @if($event->cta_ghost_href)
    <a class="btn btn-g" href="{{ $event->cta_ghost_href }}" target="_blank" rel="noopener">{{ $event->cta_ghost_label ?? 'Info' }}</a>
  @endif
</div>
@endif
@endsection

@push('scripts')
@if(!$event->is_coming_soon && $event->status !== 'completed')
<script>
const EVENT_UTC = Date.UTC({{ $event->date->year }}, {{ $event->date->month - 1 }}, {{ $event->date->day }}, {{ \Carbon\Carbon::parse($event->time)->hour }}, {{ \Carbon\Carbon::parse($event->time)->minute }}) - 8*3600000;
const target = new Date(EVENT_UTC);
function pad(n){ return String(Math.max(0,n)).padStart(2,'0'); }
function tickCd(){
  const diff = target - new Date();
  if(diff <= 0){
    ['cd-d','cd-h','cd-m','cd-s'].forEach(id => { const el = document.getElementById(id); if(el) el.textContent = '00'; });
    document.getElementById('cd-eye').textContent = 'Event sedang berlangsung';
    return;
  }
  document.getElementById('cd-d').textContent = pad(Math.floor(diff/86400000));
  document.getElementById('cd-h').textContent = pad(Math.floor(diff/3600000) % 24);
  document.getElementById('cd-m').textContent = pad(Math.floor(diff/60000) % 60);
  document.getElementById('cd-s').textContent = pad(Math.floor(diff/1000) % 60);
}
tickCd(); setInterval(tickCd, 1000);
</script>
@endif

@if($route && $route->route_points && count($route->route_points))
<script>
(function(){
  if(!window.L) return;
  const ROUTE = @json($route->route_points);
  const KM_MARKERS = @json($route->km_markers ?? []);
  const map = L.map('route-map', {center: ROUTE[0], zoom: 14, zoomControl: true, scrollWheelZoom: false});
  const tileLayers = {
    dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {attribution:'&copy; OSM &copy; CARTO', subdomains:'abcd', maxZoom:19}),
    street: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'&copy; OpenStreetMap', maxZoom:19}),
    satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {attribution:'Tiles &copy; Esri', maxZoom:19}),
    terrain: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {attribution:'Map data &copy; OSM, SRTM | &copy; OpenTopoMap', maxZoom:17})
  };
  let currentTile = tileLayers.street.addTo(map);
  L.polyline(ROUTE, {color:'rgba(74,127,255,1)', weight:14, opacity:.22, lineCap:'round', lineJoin:'round'}).addTo(map);
  const polyLine = L.polyline(ROUTE, {color:'rgba(74,127,255,1)', weight:4, opacity:1, lineCap:'round', lineJoin:'round'}).addTo(map);
  const startIcon = L.divIcon({className:'', html:'<div class="start-marker">S</div>', iconSize:[30,30], iconAnchor:[15,15]});
  L.marker(ROUTE[0], {icon:startIcon}).bindPopup('<b>START · TIKUM</b><br>{!! addslashes($event->tikum ?? '') !!}').addTo(map);
  KM_MARKERS.forEach(({km, lat, lon}) => {
    const icon = L.divIcon({className:'', html:`<div class="km-marker">${km}</div>`, iconSize:[26,26], iconAnchor:[13,13]});
    L.marker([lat, lon], {icon}).bindPopup(`<b>KM ${km}</b>`).addTo(map);
  });
  map.fitBounds(polyLine.getBounds(), {padding:[30,30]});
  document.querySelectorAll('.map-layer-btn').forEach(b => b.addEventListener('click', () => {
    const k = b.dataset.layer;
    if(!tileLayers[k]) return;
    document.querySelectorAll('.map-layer-btn').forEach(x => x.classList.toggle('active', x === b));
    map.removeLayer(currentTile);
    currentTile = tileLayers[k].addTo(map);
  }));
  window.addEventListener('resize', () => map.invalidateSize());
  map.on('click', () => map.scrollWheelZoom.enable());
  map.on('mouseout', () => map.scrollWheelZoom.disable());
})();
</script>
@endif
@endpush
