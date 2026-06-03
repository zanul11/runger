@extends('layouts.app')
@section('title', 'Agenda — ' . \App\Models\Setting::get('site.name'))

@push('styles')
<style>
  .ph{padding:36px 16px 24px;border-bottom:1px solid rgba(255,255,255,.08)}
  .ph-inner{max-width:1180px;margin:0 auto;display:flex;justify-content:space-between;align-items:flex-end;gap:20px;flex-wrap:wrap}
  .ph-eye{font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:var(--volt);margin-bottom:12px;display:inline-flex;align-items:center;gap:10px}
  .ph-eye::before{content:'';width:22px;height:1px;background:var(--volt)}
  .ph-title{font-family:'Bebas Neue',sans-serif;font-size:48px;line-height:.95}
  .ph-title em{font-style:normal;color:var(--volt)}
  .ph-sub{margin-top:6px;font-size:14px;opacity:.7;max-width:520px}
  .ph-stats{display:flex;gap:18px;font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase}
  .ph-stat .num{display:block;font-family:'Bebas Neue',sans-serif;font-size:28px;line-height:1;color:var(--volt)}
  .ph-stat .lab{opacity:.6}

  .ag-wrap{max-width:1180px;margin:0 auto;padding:20px 16px 60px}
  .ag-list{display:flex;flex-direction:column;gap:12px}
  .m-card{display:flex;flex-direction:column;gap:12px;padding:18px;border:1px solid var(--line);border-radius:6px;background:rgba(255,255,255,.02);transition:border-color .15s;cursor:pointer}
  .m-card:hover{border-color:rgba(226,240,84,.35)}
  .m-card.featured{border-color:rgba(226,240,84,.25)}
  .m-card.disabled{cursor:default}
  .m-card.disabled:hover{border-color:var(--line)}
  .mc-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
  .mc-date{font-family:'Bebas Neue',sans-serif;font-size:34px;line-height:.95;color:var(--volt);letter-spacing:0}
  .mc-date.soon{font-size:22px;letter-spacing:.04em}
  .mc-mon{font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.16em;text-transform:uppercase;opacity:.7;margin-top:3px}
  .status-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-family:'JetBrains Mono',monospace;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;font-weight:600;flex-shrink:0}
  .status-pill .dot{width:6px;height:6px;border-radius:50%;animation:pulse 1.6s infinite}
  .status-pill.upcoming{background:rgba(226,240,84,.12);color:var(--volt);border:1px solid rgba(226,240,84,.35)}
  .status-pill.upcoming .dot{background:var(--volt)}
  .status-pill.coming_soon{background:rgba(255,255,255,.05);color:#fff;border:1px solid rgba(255,255,255,.25)}
  .status-pill.coming_soon .dot{background:#fff;opacity:.6}
  .status-pill.completed{background:rgba(255,255,255,.04);color:rgba(255,255,255,.5);border:1px solid rgba(255,255,255,.15)}
  .status-pill.completed .dot{background:rgba(255,255,255,.4);animation:none}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}
  .mc-title{font-family:'Bebas Neue',sans-serif;font-size:26px;line-height:1;color:#fff}
  .mc-sub{font-family:'JetBrains Mono',monospace;font-size:11px;line-height:1.6;opacity:.7;letter-spacing:.04em;margin-top:6px}
  .mc-meta{font-family:'JetBrains Mono',monospace;font-size:11px;line-height:1.65;opacity:.75}
  .mc-meta b{color:#fff;font-weight:500}
  .mc-foot{display:flex;justify-content:space-between;align-items:center;padding-top:10px;border-top:1px solid var(--line);gap:10px}
  .dist-pill{display:inline-block;padding:7px 12px;border-radius:2px;background:rgba(226,240,84,.1);color:var(--volt);border:1px solid rgba(226,240,84,.3);font-family:'JetBrains Mono',monospace;font-size:11.5px;font-weight:600;letter-spacing:.08em}
  .dist-pill.tba{background:rgba(255,255,255,.04);color:#fff;opacity:.55;border-color:rgba(255,255,255,.18)}
  .arrow{display:inline-flex;align-items:center;gap:6px;font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.7);font-weight:600;transition:transform .2s, color .2s}
  .arrow.muted{color:rgba(255,255,255,.35)}
  .m-card:not(.disabled):hover .arrow{transform:translateX(4px);color:var(--volt)}

  @media (min-width:820px){
    .ph{padding:48px 32px 32px}
    .ph-title{font-size:72px}
    .ag-wrap{padding:24px 32px 80px}
  }
</style>
@endpush

@section('body')
<x-site-nav active="agenda" />

<header class="ph">
  <div class="ph-inner">
    <div>
      <div class="ph-eye">Agenda Runger</div>
      <h1 class="ph-title">DAFTAR <em>EVENT.</em></h1>
      <p class="ph-sub">Semua event dan long run terjadwal. Klik card untuk detail.</p>
    </div>
    <div class="ph-stats">
      <div class="ph-stat"><span class="num">{{ $upcomingCount }}</span><span class="lab">Upcoming</span></div>
      <div class="ph-stat"><span class="num">{{ $soonCount }}</span><span class="lab">Coming Soon</span></div>
      <div class="ph-stat"><span class="num">{{ $events->count() }}</span><span class="lab">Total</span></div>
    </div>
  </div>
</header>

<section class="ag-wrap">
  <div class="ag-list">
    @forelse($events as $e)
      @php
        $isSoon = $e->status === 'coming_soon';
        $statusLabel = match($e->status) {
          'upcoming' => 'Upcoming',
          'coming_soon' => 'Coming Soon',
          'completed' => 'Selesai',
          default => ucfirst($e->status),
        };
      @endphp
      <a class="m-card {{ $e->is_featured ? 'featured' : '' }}" href="{{ route('event.detail', $e->slug) }}">
        <div class="mc-head">
          <div>
            <div class="mc-date {{ $isSoon ? 'soon' : '' }}">{{ $isSoon ? 'TBA' : $e->date->format('d') }}</div>
            <div class="mc-mon">{{ strtoupper($e->date->translatedFormat('M Y')) }}</div>
          </div>
          <span class="status-pill {{ $e->status }}"><span class="dot"></span>{{ $statusLabel }}</span>
        </div>
        <div>
          <div class="mc-title">{{ $e->title }}</div>
          @if($e->subtitle)<div class="mc-sub">{{ $e->subtitle }}</div>@endif
        </div>
        <div class="mc-meta">
          <div>📅 <b>{{ $isSoon ? 'TBA' : \Carbon\Carbon::parse($e->time)->format('H.i') . ' WITA' }}</b></div>
          @if($e->tikum)<div>📍 {{ $e->tikum }}</div>@endif
          @if($e->distance_text)<div>🏃 Jarak: <b>{{ $e->distance_text }}</b></div>@endif
        </div>
        <div class="mc-foot">
          @if($e->distance_text)
            <span class="dist-pill {{ $e->distance_text === 'TBA' ? 'tba' : '' }}">{{ $e->distance_text }}</span>
          @else
            <span></span>
          @endif
          <span class="arrow">Detail →</span>
        </div>
      </a>
    @empty
      <div style="padding:60px 20px;text-align:center;font-family:'JetBrains Mono',monospace;font-size:13px;opacity:.55;border:1px dashed rgba(255,255,255,.15);border-radius:4px">
        Belum ada event terjadwal · Pantau @runnersgerung
      </div>
    @endforelse
  </div>
</section>

<x-site-footer />
@endsection
