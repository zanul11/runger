@extends('layouts.gtr')

@section('title', 'Hasil ' . $reg->full_name . ' (BIB ' . $reg->bib_number . ') — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@php
  $hms = fn ($s) => $s === null ? '—' : intdiv((int) $s, 3600) . ':' . str_pad((string) intdiv((int) $s % 3600, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ((int) $s % 60), 2, '0', STR_PAD_LEFT);
  $finisher = $result->status === 'finisher' && $result->net_time_seconds !== null;
  $km = (float) ($track['total_km'] ?: $cat->total_km ?: 0);
  $pace = $finisher && $km > 0 ? $result->net_time_seconds / $km : null; // detik per km
  $mmss = fn ($s) => $s === null ? '—' : intdiv((int) round($s), 60) . ':' . str_pad((string) ((int) round($s) % 60), 2, '0', STR_PAD_LEFT);
  $target = $reg->target_finish_minutes;
  $vsTarget = $finisher && $target ? $result->net_time_seconds - $target * 60 : null;
  $setting = \App\Models\GtrSetting::first();
  $eventDate = $setting->date_text ?? 'Minggu, 29 November 2026';

  // split per pos dari scan
  $passes = collect($track['passes'])->filter(fn ($p) => $p['passed'] && $p['km'] !== null)->sortBy('km')->values();
  $prev = ['elapsed' => 0, 'km' => 0.0];
  $splits = $passes->filter(fn ($p) => $p['type'] !== 'start')->map(function ($p) use (&$prev) {
      $seg = $p['elapsed'] - $prev['elapsed'];
      $dist = max(0.01, $p['km'] - $prev['km']);
      $row = $p + ['seg' => $seg, 'seg_pace' => $seg / $dist, 'seg_km' => $dist];
      $prev = $p;
      return $row;
  })->values();

  // rute untuk kartu story: proyeksi sederhana ke kotak 860×760
  $route = $cat->route_points ?? [];
  $routePath = '';
  if (count($route) > 1) {
      $lat0 = deg2rad(array_sum(array_column($route, 0)) / count($route));
      $xy = array_map(fn ($p) => [$p[1] * cos($lat0), -$p[0]], $route);
      $xs = array_column($xy, 0); $ys = array_column($xy, 1);
      $w = max($xs) - min($xs) ?: 1; $h = max($ys) - min($ys) ?: 1;
      $s = min(860 / $w, 760 / $h);
      $ox = 110 + (860 - $w * $s) / 2; $oy = 520 + (760 - $h * $s) / 2;
      $routePath = 'M ' . implode(' L ', array_map(fn ($p) => round($ox + ($p[0] - min($xs)) * $s, 1) . ',' . round($oy + ($p[1] - min($ys)) * $s, 1), $xy));
      $startXY = explode(',', explode(' ', $routePath)[1]);
  }
  $font = 'Helvetica, Arial, sans-serif';
@endphp

@push('styles')
<style>
  .res{padding:40px 0 64px}
  .res-back{display:inline-flex;gap:6px;font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-soft);margin-bottom:16px}
  .res-hero{position:relative;overflow:hidden;padding:22px;border-radius:22px;background:radial-gradient(500px 260px at 100% 0%,rgba(229,57,53,.28),transparent 70%),var(--bg-soft);border:1px solid var(--line)}
  .res-top{display:flex;gap:14px;align-items:center}
  .res-bib{padding:10px 13px;border-radius:13px;background:#fff;color:#101010;text-align:center;font-family:'Archivo',sans-serif;line-height:1}
  .res-bib small{display:block;font-weight:800;font-size:9px;letter-spacing:.16em;color:#8A8A8A}
  .res-bib b{display:block;font-weight:900;font-size:24px;margin-top:4px}
  .res-name{font-family:'Archivo',sans-serif;font-weight:900;font-size:clamp(20px,4vw,30px);text-transform:uppercase;line-height:1.05}
  .res-meta{color:var(--text-mute);font-size:13px;margin-top:4px}
  .res-time{font-family:'Archivo',sans-serif;font-weight:900;font-size:clamp(48px,11vw,84px);line-height:1;margin-top:20px;letter-spacing:-.02em;font-variant-numeric:tabular-nums}
  .res-time small{display:block;font-size:11px;letter-spacing:.18em;color:var(--text-mute);font-weight:800;margin-bottom:6px}
  .res-status{display:inline-block;margin-top:18px;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.08);font-family:'Archivo',sans-serif;font-weight:800;letter-spacing:.08em}
  .res-stats{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:18px}
  .res-stats div{padding:12px 14px;border-radius:14px;background:rgba(255,255,255,.05);border:1px solid var(--line)}
  .res-stats small{display:block;font-family:'Archivo',sans-serif;font-weight:700;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--text-mute)}
  .res-stats b{display:block;font-family:'Archivo',sans-serif;font-weight:900;font-size:20px;margin-top:4px}
  .res-stats b span{font-size:12px;color:var(--text-mute);font-weight:700}
  .res-h{font-family:'Archivo',sans-serif;font-weight:900;font-size:17px;text-transform:uppercase;margin:28px 0 12px}
  .sp-t{border:1px solid var(--line);border-radius:16px;overflow:hidden}
  .sp-r{display:grid;grid-template-columns:1.3fr .7fr .9fr .9fr;gap:10px;align-items:center;padding:12px 16px;border-top:1px solid var(--line);font-size:13.5px}
  .sp-r:first-child{border-top:none;background:var(--bg-soft);font-family:'Archivo',sans-serif;font-weight:800;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:var(--text-mute)}
  .sp-r b{font-family:'Archivo',sans-serif;font-weight:800}
  .sp-r .r{text-align:right}
  .sp-r small{display:block;color:var(--text-mute);font-size:11.5px}
  .dl{display:grid;grid-template-columns:1fr;gap:14px}
  .dl-card{padding:14px;border-radius:18px;background:var(--bg-soft);border:1px solid var(--line)}
  .dl-card svg{display:block;width:100%;height:auto;border-radius:10px}
  .dl-card .story{max-width:240px;margin:0 auto}
  .dl-card button{width:100%;margin-top:12px;justify-content:center}
  @media (min-width:820px){
    .res-stats{grid-template-columns:repeat(4,1fr)}
    .dl{grid-template-columns:1.6fr 1fr;align-items:start}
  }
</style>
@endpush

@section('content')
<section class="res">
  <div class="wrap">
    <a class="res-back" href="{{ route('gtr.results') }}">← Semua hasil</a>

    <div class="res-hero">
      <div class="res-top">
        <div class="res-bib"><small>BIB</small><b>{{ $reg->bib_number }}</b></div>
        <div>
          <div class="res-name">{{ $reg->full_name }}</div>
          <div class="res-meta">{{ $cat->distance }} · {{ $cat->name }}{{ $reg->club ? ' · ' . $reg->club : '' }}</div>
        </div>
      </div>

      @if($finisher)
        <div class="res-time"><small>WAKTU NET</small>{{ $result->net_time_formatted }}</div>
        <div class="res-stats">
          <div><small>Peringkat Kategori</small><b>{{ $result->rank_category }} <span>/ {{ $catTotal }}</span></b></div>
          <div><small>Peringkat {{ $reg->genderCode() === 'F' ? 'Putri' : 'Putra' }}</small><b>{{ $genderRank }} <span>/ {{ $genderTotal }}</span></b></div>
          <div><small>Pace Rata-rata</small><b>{{ $mmss($pace) }} <span>/km</span></b></div>
          <div><small>{{ $vsTarget !== null ? 'Vs Target ' . intdiv($target, 60) . ':' . str_pad((string) ($target % 60), 2, '0', STR_PAD_LEFT) : 'Gun Time' }}</small>
            <b>@if($vsTarget !== null){{ $vsTarget <= 0 ? '−' : '+' }}{{ $hms(abs($vsTarget)) }}@else{{ $result->gun_time_formatted ?? '—' }}@endif</b></div>
        </div>
      @else
        <div class="res-status">{{ strtoupper($result->status) }}</div>
      @endif
    </div>

    @if($splits->count())
      <div class="res-h">Split per Pos</div>
      <div class="sp-t">
        <div class="sp-r"><span>Pos</span><span>KM</span><span class="r">Waktu</span><span class="r">Split · Pace</span></div>
        @foreach($splits as $p)
          <div class="sp-r">
            <span><b>{{ $p['code'] }}</b><small>{{ $p['name'] }}</small></span>
            <span>{{ rtrim(rtrim(number_format($p['km'], 1), '0'), '.') }}</span>
            <span class="r"><b>{{ $hms($p['elapsed']) }}</b><small>{{ $p['at'] }}</small></span>
            <span class="r"><b>{{ $hms($p['seg']) }}</b><small>{{ $mmss($p['seg_pace']) }} /km</small></span>
          </div>
        @endforeach
      </div>
    @endif

    @if($finisher)
      <div class="res-h">E-Sertifikat &amp; Kartu Story</div>
      <div class="dl">
        <div class="dl-card">
          <svg id="cert" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 1131" width="1600" height="1131" font-family="{{ $font }}">
            <rect width="1600" height="1131" fill="#ffffff"/>
            <rect x="34" y="34" width="1532" height="1063" fill="none" stroke="#101010" stroke-width="6"/>
            <rect x="54" y="54" width="1492" height="1023" fill="none" stroke="#E53935" stroke-width="2"/>
            <rect x="54" y="54" width="1492" height="150" fill="#101010"/>
            <text x="800" y="140" fill="#ffffff" font-size="44" font-weight="900" text-anchor="middle" letter-spacing="6">GERUNG TRAIL RUN 2026</text>
            <text x="800" y="290" fill="#E53935" font-size="30" font-weight="800" text-anchor="middle" letter-spacing="10">E-CERTIFICATE · FINISHER</text>
            <text x="800" y="370" fill="#6B6B6B" font-size="28" text-anchor="middle">Diberikan kepada</text>
            <text x="800" y="480" fill="#101010" font-size="{{ mb_strlen($reg->full_name) > 24 ? 72 : 92 }}" font-weight="900" text-anchor="middle">{{ \Illuminate\Support\Str::upper($reg->full_name) }}</text>
            <line x1="420" y1="520" x2="1180" y2="520" stroke="#D4D4D4" stroke-width="2"/>
            <text x="800" y="585" fill="#444" font-size="30" text-anchor="middle">telah menyelesaikan kategori <tspan font-weight="800" fill="#101010">{{ $cat->distance }} {{ $cat->name !== $cat->distance ? '· ' . $cat->name : '' }}</tspan></text>
            <text x="800" y="630" fill="#444" font-size="26" text-anchor="middle">{{ $eventDate }} · {{ \Illuminate\Support\Str::limit($setting->location_text ?? 'Bukit Keteri, Gerung — Lombok Barat', 60) }}</text>
            <g transform="translate(330,700)">
              <rect width="300" height="150" rx="18" fill="#F5F5F5"/>
              <text x="150" y="52" fill="#8A8A8A" font-size="20" font-weight="700" text-anchor="middle" letter-spacing="3">WAKTU NET</text>
              <text x="150" y="112" fill="#101010" font-size="52" font-weight="900" text-anchor="middle">{{ $result->net_time_formatted }}</text>
            </g>
            <g transform="translate(650,700)">
              <rect width="300" height="150" rx="18" fill="#F5F5F5"/>
              <text x="150" y="52" fill="#8A8A8A" font-size="20" font-weight="700" text-anchor="middle" letter-spacing="3">PERINGKAT</text>
              <text x="150" y="112" fill="#101010" font-size="52" font-weight="900" text-anchor="middle">{{ $result->rank_category }}/{{ $catTotal }}</text>
            </g>
            <g transform="translate(970,700)">
              <rect width="300" height="150" rx="18" fill="#F5F5F5"/>
              <text x="150" y="52" fill="#8A8A8A" font-size="20" font-weight="700" text-anchor="middle" letter-spacing="3">BIB</text>
              <text x="150" y="112" fill="#101010" font-size="52" font-weight="900" text-anchor="middle">{{ $reg->bib_number }}</text>
            </g>
            <line x1="1080" y1="990" x2="1440" y2="990" stroke="#101010" stroke-width="2"/>
            <text x="1260" y="1025" fill="#444" font-size="22" text-anchor="middle">Panitia Gerung Trail Run 2026</text>
            <text x="160" y="1025" fill="#9A9A9A" font-size="20">Diterbitkan oleh Runger · Runners Gerung</text>
          </svg>
          <button type="button" class="btn-primary" data-dl="cert" data-name="sertifikat-gtr2026-{{ $reg->bib_number }}.png">Download E-Sertifikat <span class="arr">↓</span></button>
        </div>

        <div class="dl-card">
          <svg id="story" class="story" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1920" width="1080" height="1920" font-family="{{ $font }}">
            <defs>
              <radialGradient id="sg" cx="85%" cy="5%" r="70%"><stop offset="0" stop-color="#5a1312"/><stop offset="1" stop-color="#0A0A0A"/></radialGradient>
            </defs>
            <rect width="1080" height="1920" fill="url(#sg)"/>
            <text x="90" y="200" fill="#FF8A80" font-size="34" font-weight="800" letter-spacing="8">GERUNG TRAIL RUN 2026</text>
            <text x="90" y="330" fill="#ffffff" font-size="150" font-weight="900">{{ $cat->distance }}</text>
            <text x="90" y="400" fill="#B4B4B4" font-size="36" font-weight="700">{{ $eventDate }}</text>
            @if($routePath)
              <path d="{{ $routePath }}" fill="none" stroke="#000000" stroke-opacity=".5" stroke-width="22" stroke-linejoin="round" stroke-linecap="round"/>
              <path d="{{ $routePath }}" fill="none" stroke="#E53935" stroke-width="12" stroke-linejoin="round" stroke-linecap="round"/>
              <circle cx="{{ $startXY[0] }}" cy="{{ $startXY[1] }}" r="18" fill="#ffffff" stroke="#E53935" stroke-width="8"/>
            @endif
            <text x="90" y="1430" fill="#B4B4B4" font-size="34" font-weight="800" letter-spacing="8">FINISHER</text>
            <text x="90" y="1590" fill="#ffffff" font-size="190" font-weight="900">{{ $result->net_time_formatted }}</text>
            <text x="90" y="1680" fill="#ffffff" font-size="54" font-weight="900">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($reg->full_name, 22)) }}</text>
            <text x="90" y="1750" fill="#B4B4B4" font-size="36" font-weight="700">#{{ $result->rank_category }} dari {{ $catTotal }} · BIB {{ $reg->bib_number }}{{ $cat->elevation_gain ? ' · ' . $cat->elevation_gain : '' }}</text>
            <rect x="90" y="1800" width="900" height="6" fill="#E53935"/>
          </svg>
          <button type="button" class="btn-primary" data-dl="story" data-name="story-gtr2026-{{ $reg->bib_number }}.png">Download Kartu Story <span class="arr">↓</span></button>
        </div>
      </div>
    @endif
  </div>
</section>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-dl]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var svg = document.getElementById(btn.dataset.dl);
      var w = svg.viewBox.baseVal.width, h = svg.viewBox.baseVal.height;
      var img = new Image();
      img.onload = function () {
        var c = document.createElement('canvas');
        c.width = w; c.height = h;
        c.getContext('2d').drawImage(img, 0, 0, w, h);
        c.toBlob(function (blob) {
          var a = document.createElement('a');
          a.href = URL.createObjectURL(blob); a.download = btn.dataset.name;
          document.body.appendChild(a); a.click(); a.remove();
          setTimeout(function () { URL.revokeObjectURL(a.href); }, 2000);
        }, 'image/png');
      };
      img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(new XMLSerializer().serializeToString(svg));
    });
  });
</script>
@endpush
