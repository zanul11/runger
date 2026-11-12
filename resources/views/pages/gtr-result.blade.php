@extends('layouts.gtr')

@section('title', 'Result: ' . $reg->full_name . ' (BIB ' . $reg->bib_number . ') — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@php
  $hms = fn ($s) => $s === null ? '—' : intdiv((int) $s, 3600) . ':' . str_pad((string) intdiv((int) $s % 3600, 60), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ((int) $s % 60), 2, '0', STR_PAD_LEFT);
  // Waktu resmi = gun time (dasar peringkat). Pace & race plan memakai net time bila ada scan START.
  $finisher = $result->status === 'finisher' && $result->gun_time_seconds !== null;
  $runSec = $result->net_time_seconds ?? $result->gun_time_seconds;
  $km = (float) ($track['total_km'] ?: $cat->total_km ?: 0);
  $pace = $finisher && $km > 0 ? $runSec / $km : null; // detik per km
  $mmss = fn ($s) => $s === null ? '—' : intdiv((int) round($s), 60) . ':' . str_pad((string) ((int) round($s) % 60), 2, '0', STR_PAD_LEFT);
  $target = $reg->target_finish_minutes;
  $vsTarget = $finisher && $target ? $runSec - $target * 60 : null;
  $setting = \App\Models\GtrSetting::first();
  // Halaman ini berbahasa Inggris: nama hari/bulan dari setting (Indonesia) diterjemahkan.
  $eventDate = strtr($setting->date_text ?? 'Minggu, 29 November 2026', [
      'Senin' => 'Monday', 'Selasa' => 'Tuesday', 'Rabu' => 'Wednesday', 'Kamis' => 'Thursday', 'Jumat' => 'Friday', "Jum'at" => 'Friday', 'Sabtu' => 'Saturday', 'Minggu' => 'Sunday',
      'Januari' => 'January', 'Februari' => 'February', 'Maret' => 'March', 'Mei' => 'May', 'Juni' => 'June', 'Juli' => 'July', 'Agustus' => 'August', 'Oktober' => 'October', 'Desember' => 'December',
  ]);

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

  // Data kartu finisher (e-certificate & story) — digambar di <canvas> oleh public/js/gtr-cards.js.
  $catNo = preg_replace('/\D+/', '', (string) $cat->name);
  $card = [
      'theme' => $catNo === '7' ? 'green' : 'red', // 7K hijau, kategori lain merah (sesuai desain)
      'name' => $reg->full_name,
      'bib' => (string) $reg->bib_number,
      'gender' => match ($reg->genderCode()) { 'M' => 'MALE', 'F' => 'FEMALE', default => '' },
      'cat' => $cat->name,
      'tag' => \Illuminate\Support\Str::upper((string) $cat->tag),
      'tagTitle' => \Illuminate\Support\Str::title((string) $cat->tag),
      'distance' => $catNo !== '' ? $catNo . ' KM' : $cat->distance,
      'gain' => preg_replace('/\D+/', '', (string) $cat->elevation_gain),
      'time' => $result->gun_time_formatted,
      // Peringkat di kartu = peringkat gender dalam kategori (kolom di sebelahnya "15K / MALE").
      'rank' => (string) ($genderRank ?? $result->rank_category),
      'certNo' => 'GTR26/' . $cat->name . '/' . $reg->bib_number,
      'date' => $eventDate,
      'ele' => array_values(array_map(fn ($p) => (float) ($p[1] ?? 0), $cat->elevation_profile ?? [])), // siluet bukit di latar
      'route' => array_values(array_map(fn ($p) => [round((float) $p[0], 5), round((float) $p[1], 5)], $cat->route_points ?? [])),
      'assets' => [
          'gtr' => asset('assets/gtr/bib-check/gtr-logo.webp'),
          'rungerBadge' => asset('assets/runger-logo.png'),
          'rungerWhite' => asset('assets/runger-logo-white.png'),
          // Logo sponsor yang sama dengan kiosk Cek BIB / Cek Hasil.
          'sponsors' => [
              ['src' => asset('assets/gtr/bib-check/sponsor-alti.webp')],
              ['src' => asset('assets/gtr/bib-check/sponsor-lobar.webp')],
              ['src' => asset('assets/gtr/bib-check/sponsor-dispar.webp'), 'wide' => true],
              ['src' => asset('assets/gtr/bib-check/bank-ntb-syariah.webp'), 'wide' => true],
              ['src' => asset('assets/gtr/bib-check/pam.webp')],
          ],
      ],
  ];
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
  .dl-card{padding:14px;border-radius:18px;background:var(--bg-soft);border:1px solid var(--line);display:flex;flex-direction:column}
  .dl-card small{display:block;font-family:'Archivo',sans-serif;font-weight:700;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--text-mute);margin-bottom:10px}
  .dl-card canvas{display:block;width:100%;height:auto;border-radius:10px;background:rgba(255,255,255,.05)}
  .dl-card .tall{max-width:300px;margin:0 auto}
  .dl-card button{width:100%;margin-top:auto;justify-content:center}
  .dl-card canvas + button{margin-top:12px}
  @media (min-width:820px){
    .res-stats{grid-template-columns:repeat(4,1fr)}
    .dl{grid-template-columns:1.6fr 1fr;align-items:start}
  }
</style>
@endpush

@section('content')
<section class="res">
  <div class="wrap">
    <a class="res-back" href="{{ route('gtr.results') }}">← All results</a>

    <div class="res-hero">
      <div class="res-top">
        <div class="res-bib"><small>BIB</small><b>{{ $reg->bib_number }}</b></div>
        <div>
          <div class="res-name">{{ $reg->full_name }}</div>
          <div class="res-meta">{{ $cat->distance }} · {{ $cat->name }}{{ $reg->club ? ' · ' . $reg->club : '' }}</div>
        </div>
      </div>

      @if($finisher)
        <div class="res-time"><small>OFFICIAL TIME</small>{{ $result->gun_time_formatted }}</div>
        <div class="res-stats">
          <div><small>Category Rank</small><b>{{ $result->rank_category }} <span>/ {{ $catTotal }}</span></b></div>
          <div><small>{{ $reg->genderCode() === 'F' ? 'Female' : 'Male' }} Rank</small><b>{{ $genderRank }} <span>/ {{ $genderTotal }}</span></b></div>
          <div><small>Average Pace</small><b>{{ $mmss($pace) }} <span>/km</span></b></div>
          <div><small>{{ $vsTarget !== null ? 'Vs Target ' . intdiv($target, 60) . ':' . str_pad((string) ($target % 60), 2, '0', STR_PAD_LEFT) : ($result->net_time_formatted ? 'Net Time' : 'Finish Time') }}</small>
            <b>@if($vsTarget !== null){{ $vsTarget <= 0 ? '−' : '+' }}{{ $hms(abs($vsTarget)) }}@else{{ $result->net_time_formatted ?? ($result->finish_time ? $result->finish_time->format('H:i:s') : '—') }}@endif</b></div>
        </div>
      @else
        <div class="res-status">{{ strtoupper($result->status) }}</div>
      @endif
    </div>

    @if($splits->count())
      <div class="res-h">Checkpoint Splits</div>
      <div class="sp-t">
        <div class="sp-r"><span>Checkpoint</span><span>KM</span><span class="r">Time</span><span class="r">Split · Pace</span></div>
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
      <div class="res-h">E-Certificate &amp; Story Card</div>
      <div class="dl">
        <div class="dl-card">
          <small>E-Certificate · A4 landscape</small>
          <canvas data-card="cert" width="1123" height="794" role="img" aria-label="Finisher e-certificate for {{ $reg->full_name }}"></canvas>
          <button type="button" class="btn-primary" data-dl="cert" data-name="certificate-gtr2026-{{ $reg->bib_number }}.png">Download E-Certificate <span class="arr">↓</span></button>
        </div>

        <div class="dl-card">
          <small>Instagram Story · 9:16</small>
          <canvas class="tall" data-card="story" width="1080" height="1920" role="img" aria-label="Finisher story card for {{ $reg->full_name }}"></canvas>
          <button type="button" class="btn-primary" data-dl="story" data-name="story-gtr2026-{{ $reg->bib_number }}.png">Download Story Card <span class="arr">↓</span></button>
        </div>
      </div>
    @endif
  </div>
</section>
@endsection

@if($finisher)
@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700&family=Barlow+Condensed:ital,wght@0,700;0,800;1,900&display=swap" rel="stylesheet">
@endpush
@push('scripts')
<script>window.GTR_CARD = {{ \Illuminate\Support\Js::from($card) }};</script>
<script src="{{ asset('js/gtr-cards.js') }}?v={{ filemtime(public_path('js/gtr-cards.js')) }}"></script>
@endpush
@endif
