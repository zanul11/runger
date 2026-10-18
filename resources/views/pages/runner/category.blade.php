@extends('layouts.runner-app')
@section('title', $cat->distance . ' — Detail Kategori — Runger')
@section('app-title', 'Detail ' . $cat->distance)

@php
  $awards = collect(preg_split('/\r?\n/', (string) $cat->award_prize))
    ->map(fn ($l) => trim(ltrim(trim($l), '-•*· ')))
    ->filter()->values();
  $cpCount = $cat->coursePoints()->where('type', 'cp')->count();
@endphp

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600..900&display=swap" rel="stylesheet">
@verbatim
<style>
  .cd-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;font-weight:700;font-size:12px;color:var(--soft)}
  /* hero */
  .cd-hero{position:relative;overflow:hidden;isolation:isolate;border-radius:22px;color:#fff;background:#0A0A0A;box-shadow:0 16px 38px rgba(10,10,10,.35)}
  .cd-hero .ph{position:absolute;inset:0;z-index:-2;background-size:cover;background-position:center}
  .cd-hero::before{content:'';position:absolute;inset:0;z-index:-1;background:linear-gradient(180deg,rgba(10,10,10,.2) 0%,rgba(10,10,10,.55) 50%,rgba(10,10,10,.95) 100%)}
  .cd-hero .in{padding:110px 18px 18px}
  .cd-tag{display:inline-flex;align-items:center;gap:7px;padding:5px 11px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(8px);font-weight:800;font-size:9.5px;letter-spacing:.08em;text-transform:uppercase}
  .cd-tag i{width:7px;height:7px;border-radius:50%;background:var(--cc)}
  .cd-dist{font-family:'Archivo','Poppins',sans-serif;font-weight:900;font-size:58px;line-height:.9;letter-spacing:-.03em;margin-top:10px}
  .cd-name{font-weight:700;font-size:13px;letter-spacing:.06em;text-transform:uppercase;opacity:.85;margin-top:4px}
  .cd-metrics{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:16px}
  .cd-metrics div{padding:9px 6px;border-radius:12px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.14);text-align:center;min-width:0}
  .cd-metrics b{display:block;font-weight:800;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .cd-metrics small{display:block;font-weight:700;font-size:7.5px;letter-spacing:.1em;text-transform:uppercase;opacity:.6;margin-top:3px}

  /* price / CTA */
  .cd-buy{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding:14px 16px;border-radius:18px;background:#fff;border:1px solid var(--line);box-shadow:0 6px 18px rgba(15,24,48,.06)}
  .cd-buy .lab{font-weight:800;font-size:9px;letter-spacing:.12em;text-transform:uppercase;color:var(--mute)}
  .cd-buy .val{font-weight:900;font-size:20px;color:var(--text);line-height:1.1;margin-top:2px}
  .cd-buy .val s{font-size:12px;color:var(--mute);font-weight:600;margin-left:4px}
  .cd-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 18px;border-radius:999px;background:var(--red);color:#fff;font-weight:800;font-size:12px;letter-spacing:.04em;text-transform:uppercase;white-space:nowrap;box-shadow:0 8px 18px rgba(229,57,53,.3)}
  .cd-btn.done{background:#DCFCE7;color:#15803D;box-shadow:none;border:1px solid #A7F3D0}

  /* tabs */
  .cd-tabs{display:grid;grid-template-columns:repeat(4,1fr);gap:4px;padding:4px;margin:18px 0 12px;border-radius:16px;background:#fff;border:1px solid var(--line)}
  .cd-tab{display:flex;flex-direction:column;align-items:center;gap:4px;padding:9px 4px 8px;border-radius:12px;border:none;background:none;font-family:'Poppins',sans-serif;font-weight:700;font-size:11px;color:var(--soft);cursor:pointer;white-space:nowrap}
  .cd-tab svg{width:19px;height:19px}
  .cd-tab.active{background:#101010;color:#fff}
  .cd-tab.active svg{color:#FF8A80}
  .cd-panel{display:none;background:#fff;border:1px solid var(--line);border-radius:20px;padding:14px;color:#101010}
  .cd-panel.active{display:block}

  /* dipakai partial course map */
  .tab-note{display:flex;align-items:center;gap:8px;font-size:12px;color:#8A8A8A;margin-top:14px}
  .tab-note::before{content:'';width:6px;height:6px;border-radius:50%;background:#D0D0D0;flex-shrink:0}
  .tab-img{width:100%;max-height:240px;object-fit:cover;border-radius:14px;display:block}
  .cd-panel .sec-head h3{font-size:15px}

  /* award */
  .cd-award{margin-top:12px;padding:14px 16px;border-radius:18px;background:#fff;border:1px solid var(--line)}
  .cd-award h4{font-weight:800;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--mute);margin-bottom:8px}
  .cd-award li{list-style:none;display:flex;gap:9px;font-size:13px;font-weight:600;color:var(--text);line-height:1.5}
  .cd-award li::before{content:'';width:6px;height:6px;border-radius:50%;background:#F5BE3C;margin-top:8px;flex-shrink:0}

  /* rundown */
  .rd{position:relative;display:flex;flex-direction:column;gap:8px;padding-left:20px}
  .rd::before{content:'';position:absolute;left:5px;top:10px;bottom:10px;width:2px;background:linear-gradient(180deg,var(--red),#EDEDED)}
  .rd-row{position:relative;padding:11px 13px;border-radius:14px;background:#F7F7F7;border:1px solid #EFEFEF}
  .rd-row::before{content:'';position:absolute;left:-20px;top:15px;width:11px;height:11px;border-radius:50%;background:#fff;border:3px solid var(--red)}
  .rd-t{display:flex;gap:8px;align-items:baseline}
  .rd-t b{font-weight:900;font-size:16px;color:var(--red)}
  .rd-t span{font-weight:700;font-size:10px;letter-spacing:.06em;text-transform:uppercase;color:#9A9A9A}
  .rd-a{font-size:13.5px;font-weight:600;margin-top:2px}
  .rd-l{font-size:12px;color:#8A8A8A;margin-top:2px}

  /* gear */
  .gr{display:flex;flex-direction:column;gap:8px}
  .gr-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-size:12px;color:#8A8A8A;font-weight:600}
  .gr-head b{font-size:12px;color:#101010}
  .gr label{display:flex;gap:11px;align-items:center;padding:11px 13px;border-radius:14px;background:#F7F7F7;border:1px solid #EFEFEF;font-size:13.5px;font-weight:600;cursor:pointer}
  .gr input{position:absolute;opacity:0;pointer-events:none}
  .gr-box{width:26px;height:26px;border-radius:9px;border:2px solid #D4D4D4;background:#fff;display:grid;place-items:center;flex-shrink:0;color:transparent}
  .gr-box svg{width:15px;height:15px}
  .gr input:checked + .gr-box{background:#22C55E;border-color:#22C55E;color:#fff}
  .gr label:has(input:checked){background:#F0FDF4;border-color:#BBF7D0}

  /* gpx */
  .gx{display:flex;flex-direction:column;gap:14px;align-items:flex-start}
  .gx-file{display:flex;align-items:center;gap:12px}
  .gx-ic{width:46px;height:54px;border-radius:11px;display:grid;place-items:center;background:#101010;color:#fff;font-family:'Archivo','Poppins',sans-serif;font-weight:900;font-size:11px}
  .gx-file b{display:block;font-weight:800;font-size:14.5px}
  .gx-file small{font-size:12px;color:#8A8A8A}
  .gx-lock{display:inline-flex;align-items:center;gap:7px;padding:11px 16px;border-radius:999px;background:#F3F4F6;font-weight:700;font-size:11.5px;color:#6B7280}
  .gx-lock svg{width:15px;height:15px}
  .empty{font-size:13px;color:#8A8A8A}
</style>
@endverbatim
@endpush

@section('content')
<a class="cd-back" href="{{ route('gtr.dashboard') }}">← Kembali</a>

<section class="cd-hero" style="--cc:{{ $cat->color ?: '#E53935' }}">
  <div class="ph" style="background-image:url('{{ $cat->header_url }}')"></div>
  <div class="in">
    <span class="cd-tag"><i></i>{{ $cat->tag }}</span>
    <div class="cd-dist">{{ $cat->distance }}</div>
    @if(strtoupper(preg_replace('/\s+/', '', $cat->name)) !== strtoupper(preg_replace('/\s+/', '', $cat->distance)))
      <div class="cd-name">{{ $cat->name }}</div>
    @endif
    <div class="cd-metrics">
      <div><b>{{ trim(preg_replace('/\s*WITA\s*/i', '', (string) $cat->start_time)) ?: '-' }}</b><small>Start WITA</small></div>
      <div><b>{{ $cat->elevation_gain ?: '-' }}</b><small>Elevasi</small></div>
      <div><b>{{ $cat->cut_off_time ?: '-' }}</b><small>COT</small></div>
      @if($cpCount)
        <div><b>{{ $cat->water_station_count }} / {{ $cpCount }}</b><small>WS / CP</small></div>
      @else
        <div><b>{{ $cat->water_station_count }}</b><small>Water St.</small></div>
      @endif
    </div>
  </div>
</section>

<div class="cd-buy">
  <div>
    <div class="lab">{{ $cat->currentPriceLabel() }}</div>
    <div class="val">
      @if($cat->earlyBirdActive())
        {{ $cat->early_bird_formatted }}<s>{{ $cat->normal_formatted }}</s>
      @else
        {{ $cat->current_price_formatted }}
      @endif
    </div>
  </div>
  @if($registration)
    <a class="cd-btn done" href="{{ route('gtr.account.race.detail', $registration) }}">✓ Terdaftar</a>
  @else
    <a class="cd-btn" href="{{ route('gtr.register.form', $cat) }}">Daftar →</a>
  @endif
</div>

@if($awards->count())
<div class="cd-award">
  <h4>Award &amp; Prize</h4>
  <ul>@foreach($awards as $a)<li>{{ $a }}</li>@endforeach</ul>
</div>
@endif

<div class="cd-tabs" id="cd-tabs" role="tablist">
  <button type="button" class="cd-tab active" data-tab="map"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>Rute</button>
  <button type="button" class="cd-tab" data-tab="rundown"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Rundown</button>
  <button type="button" class="cd-tab" data-tab="gear"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Gear</button>
  <button type="button" class="cd-tab" data-tab="gpx"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>GPX</button>
</div>

<div class="cd-panel active" data-panel="map">
  @include('pages.partials.gtr-course', ['cat' => $cat, 'variant' => 'app', 'registration' => $registration])
</div>

<div class="cd-panel" data-panel="rundown">
  @if(!empty($cat->rundown))
    <div class="rd">
      @foreach($cat->rundown as $row)
        <div class="rd-row">
          <div class="rd-t"><b>{{ $row['time'] ?? '' }}</b>@if(!empty($row['date']))<span>{{ $row['date'] }}</span>@endif</div>
          <div class="rd-a">{{ $row['activity'] ?? '' }}</div>
          @if(!empty($row['location']))<div class="rd-l">📍 {{ $row['location'] }}</div>@endif
        </div>
      @endforeach
    </div>
  @else
    <p class="empty">Rundown acara akan diumumkan menjelang race day.</p>
  @endif
</div>

<div class="cd-panel" data-panel="gear">
  @if(!empty($cat->mandatory_gear))
    <div class="gr-head"><span>Centang yang sudah siap</span><b data-gear-count></b></div>
    <div class="gr" data-gear="gtr-gear-{{ $cat->id }}">
      @foreach($cat->mandatory_gear as $i => $item)
        <label><input type="checkbox" value="{{ $i }}"><span class="gr-box"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>{{ $item }}</label>
      @endforeach
    </div>
  @else
    <p class="empty">Daftar mandatory gear akan diumumkan menjelang race day.</p>
  @endif
</div>

<div class="cd-panel" data-panel="gpx">
  <div class="gx">
    <div class="gx-file">
      <span class="gx-ic">GPX</span>
      <div>
        <b>{{ $cat->name }} · {{ $cat->distance }}</b>
        <small>{{ $cat->canDownloadGpx() ? 'Untuk GPS / smartwatch kamu.' : 'Akan bisa diunduh setelah dibuka panitia.' }}</small>
      </div>
    </div>
    @if($cat->canDownloadGpx())
      <a class="cd-btn" href="{{ route('gtr.category.gpx', $cat->slug) }}">Download GPX ↓</a>
    @elseif($cat->gpx_file)
      <span class="gx-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Download GPX belum dibuka panitia</span>
    @else
      <p class="empty">File GPX belum tersedia — segera diunggah panitia.</p>
    @endif
  </div>
</div>

<div style="margin-top:16px">
  @include('pages.partials.gtr-weather', ['style' => 'card', 'theme' => 'light'])
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var tabs = document.querySelectorAll('#cd-tabs .cd-tab');
    var panels = document.querySelectorAll('.cd-panel');
    tabs.forEach(function (btn) {
      btn.addEventListener('click', function () {
        tabs.forEach(function (b) { b.classList.toggle('active', b === btn); });
        panels.forEach(function (p) { p.classList.toggle('active', p.dataset.panel === btn.dataset.tab); });
        if (btn.dataset.tab === 'map' && window._gtrMap) setTimeout(function () { window._gtrMap.invalidateSize(); }, 80);
      });
    });

    // checklist gear — tersimpan di HP (localStorage)
    var gear = document.querySelector('[data-gear]');
    if (gear) {
      var key = gear.dataset.gear, saved = [];
      try { saved = JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) {}
      var boxes = gear.querySelectorAll('input'), count = document.querySelector('[data-gear-count]');
      var sync = function () {
        var on = [].filter.call(boxes, function (b) { return b.checked; }).map(function (b) { return b.value; });
        count.textContent = on.length + ' / ' + boxes.length + (on.length === boxes.length ? ' ✓ siap' : '');
        try { localStorage.setItem(key, JSON.stringify(on)); } catch (e) {}
      };
      boxes.forEach(function (b) { b.checked = saved.indexOf(b.value) !== -1; b.addEventListener('change', sync); });
      sync();
    }
  })();
</script>
@endpush
