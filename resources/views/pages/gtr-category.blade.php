@extends('layouts.gtr')

@section('title', $cat->name . ' (' . $cat->distance . ') — Gerung Trail Run 2026')

@section('content')

<!-- DETAIL HEAD: mobile = foto full bg + judul overlay; desktop = poster kiri + judul & statistik kanan -->
@php
  $norm = fn ($s) => strtoupper(preg_replace('/\s+/', '', (string) $s));
  $showName = $norm($cat->name) !== $norm($cat->distance);
  $awards = collect(preg_split('/\r?\n/', (string) $cat->award_prize))
    ->map(fn ($l) => trim(ltrim(trim($l), '-•*· ')))
    ->filter()->values();
@endphp
<div class="cat-detail" style="--cat-photo:url('{{ $cat->header_url }}');--cat-color:{{ $cat->color }}">
  <div class="cat-hero-bg"></div>
  <div class="cat-poster">
    <img src="{{ $cat->header_url }}" alt="{{ $cat->name }}">
    <div class="poster-badge"><span class="pb-day">29</span><span class="pb-mon">Nov 2026</span></div>
  </div>
  <div class="cat-hero-inner">
    <a class="back" href="{{ route('gtr') }}#category">← Semua Kategori</a>
    <span class="ch-tag"><span class="dot" style="background:{{ $cat->color }}"></span>{{ $cat->tag }}</span>
    <h1>{{ $cat->distance }}</h1>
    @if($showName)<div class="ch-name">{{ $cat->name }}</div>@endif
  </div>
  <div class="cat-stats-wrap">
    <div class="cat-metrics">
      <div class="cat-metric">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg></span>
        <div>
          <div class="lab">Start</div>
          <div class="val">{{ $cat->start_time ?: '-' }}</div>
          <div class="sub">29 November 2026</div>
        </div>
      </div>
      <div class="cat-metric">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 20l6-11 4 6 3-4 5 9z"/></svg></span>
        <div>
          <div class="lab">Elevation Gain</div>
          <div class="val">{{ $cat->elevation_gain ?: '-' }}</div>
        </div>
      </div>
      <div class="cat-metric">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12M6 22h12M7 2v4l5 6-5 6v4M17 2v4l-5 6 5 6v4"/></svg></span>
        <div>
          <div class="lab">Cut-Off Time</div>
          <div class="val">{{ $cat->cut_off_time ?: '-' }}</div>
        </div>
      </div>
      <div class="cat-metric">
        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linejoin="round"><path d="M12 3c0 0-6 6.6-6 11a6 6 0 0 0 12 0c0-4.4-6-11-6-11z"/></svg></span>
        <div>
          <div class="lab">Water Station</div>
          <div class="val">{{ $cat->water_station_label }}</div>
        </div>
      </div>
    </div>

    <div class="cat-cards">
      <div class="price-card">
        <div>
          <div class="lab">Reg Fee</div>
          @if($cat->earlyBirdActive())
            <div class="val">{{ $cat->early_bird_formatted }} <span class="strike">{{ $cat->normal_formatted }}</span></div>
            <span class="sub">⚡ Early Bird{{ $cat->early_bird_until ? ' s/d ' . $cat->early_bird_until->translatedFormat('d M Y') : '' }}</span>
          @else
            <div class="val">{{ $cat->current_price_formatted }}</div>
            <span class="sub">Harga Normal @if($cat->earlyBirdEnded())· early bird selesai @endif</span>
          @endif
        </div>
        <a class="btn-reg" href="{{ auth('runner')->check() ? route('gtr.dashboard') : route('gtr.login') }}">Register Now <span class="arr">→</span></a>
      </div>
      <div class="award-card">
        <div class="head">
          <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/></svg></span>
          <span class="lab">Award & Prize</span>
        </div>
        @if($awards->count())
          <ul class="award-list">
            @foreach($awards as $a)<li>{{ $a }}</li>@endforeach
          </ul>
        @else
          <ul class="award-list"><li>-</li></ul>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- TABS SECTION -->
<section class="detail-section">
  <div class="detail-wrap">

    <!-- TABS -->
    <div class="tabs" id="cat-tabs">
      <div class="tab-btns" role="tablist">
        <button class="tab-btn active" data-tab="map" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>Course Map</button>
        <button class="tab-btn" data-tab="rundown" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Event Rundown</button>
        <button class="tab-btn" data-tab="gear" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>Mandatory Gear</button>
        <button class="tab-btn" data-tab="gpx" type="button"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>GPX File</button>
      </div>

      <div class="tab-panel active" data-panel="map">
        @include('pages.partials.gtr-course', ['cat' => $cat])
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
                  @if(!empty($row['location']))<span class="loc"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $row['location'] }}</span>@endif
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
                <span class="chk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg></span>
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
          <div class="gpx-file">
            <span class="ic">GPX</span>
            <div>
              <div class="nm">{{ $cat->name }} · {{ $cat->distance }}</div>
              <p class="ds">{{ $cat->canDownloadGpx() ? 'Bisa diunduh untuk dipakai di GPS / smartwatch.' : 'File akan bisa diunduh setelah dibuka panitia.' }}</p>
            </div>
          </div>
          @if($cat->canDownloadGpx())
            <a class="btn-primary" href="{{ route('gtr.category.gpx', $cat->slug) }}">Download GPX <span class="arr">↓</span></a>
          @elseif($cat->gpx_file)
            <span class="gpx-lock"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Download GPX belum dibuka panitia</span>
          @else
            <span class="tab-note">File GPX belum tersedia — segera diunggah panitia.</span>
          @endif
        </div>
      </div>
    </div>

    <div class="cat-cta">
      <div>
        <div class="k">Gerung Trail Run 2026</div>
        <h3>Siap taklukkan {{ $cat->distance }}?</h3>
      </div>
      <a class="btn-primary" href="{{ auth('runner')->check() ? route('gtr.dashboard') : route('gtr.login') }}">Register Now <span class="arr">→</span></a>
    </div>

    <div style="margin-top:20px">
      @include('pages.partials.gtr-weather', ['style' => 'card', 'theme' => 'dark'])
    </div>
  </div>
</section>

@push('scripts')
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
  })();
</script>
@endverbatim
@endpush

@endsection
