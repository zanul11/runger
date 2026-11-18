@extends('layouts.runner-app')
@section('title', $cat->distance . ' — Detail Kategori — Runger')
@section('app-title', 'Detail ' . $cat->distance)

@php
  $awards = collect(preg_split('/\r?\n/', (string) $cat->award_prize))
    ->map(fn ($l) => trim(ltrim(trim($l), '-•*· ')))
    ->filter()->values();
  $cpCount = $cat->coursePoints()->where('type', 'cp')->count();
  $copCount = $cat->coursePoints()->where('type', 'cop')->count();
@endphp

@push('styles')
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

  /* award */
  .cd-award{margin-top:12px;padding:14px 16px;border-radius:18px;background:#fff;border:1px solid var(--line)}
  .cd-award h4{font-weight:800;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--mute);margin-bottom:8px}
  .cd-award li{list-style:none;display:flex;gap:9px;font-size:13px;font-weight:600;color:var(--text);line-height:1.5}
  .cd-award li::before{content:'';width:6px;height:6px;border-radius:50%;background:#F5BE3C;margin-top:8px;flex-shrink:0}

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
      @if($cpCount || $copCount)
        <div><b>{{ $cat->water_station_count }} / {{ $cpCount }}{{ $copCount ? ' / ' . $copCount : '' }}</b><small>WS / CP{{ $copCount ? ' / COP' : '' }}</small></div>
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
  @elseif(\App\Models\GtrSetting::registrationClosed())
    <span class="cd-btn done">Pendaftaran ditutup</span>
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

<div style="margin-top:18px">
  @include('pages.runner.partials.course-tabs', ['cat' => $cat, 'registration' => $registration])
</div>

<div style="margin-top:16px">
  @include('pages.partials.gtr-weather', ['style' => 'card', 'theme' => 'light'])
</div>
@endsection
