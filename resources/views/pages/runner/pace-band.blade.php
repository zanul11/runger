@extends('layouts.runner-app')
@section('title', 'Pace Band ' . $cat->distance . ' — Runger')
@section('app-title', 'Pace Band')

@php
  $hm = fn ($m) => intdiv((int) $m, 60) . ':' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT);
  $clock = fn ($m) => $m === null ? '-' : str_pad((string) (intdiv((int) $m, 60) % 24), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT);
  $color = ['start' => '#22C55E', 'finish' => '#101010', 'ws' => '#2EA6E6', 'cp' => '#F97316', 'cop' => '#DC2626'];

  // ukuran pace band (SVG, unit px) — 360 lebar, tinggi mengikuti jumlah pos
  $W = 360; $head = 112; $rowH = 46; $segH = 22; $foot = 40; $kmH = 34; $kmHead = 24;
  $H = $mode === 'km'
    ? $head + $kmHead + count($splits) * $kmH + $foot
    : $head + count($plan) * $rowH + max(0, count($plan) - 1) * $segH + $foot;
  $mmss = fn ($m) => intdiv((int) round($m * 60), 60) . ':' . str_pad((string) ((int) round($m * 60) % 60), 2, '0', STR_PAD_LEFT);
  $tone = fn ($s) => $s['gain'] >= 60 && $s['loss'] >= 60 ? '#F97316'
    : ($s['grade'] < -3 ? '#2563EB' : ($s['grade'] < 3 ? '#22C55E' : ($s['grade'] < 8 ? '#EAB308' : ($s['grade'] < 15 ? '#F97316' : '#DC2626'))));
  $font = 'Helvetica, Arial, sans-serif';
@endphp

@push('styles')
@verbatim
<style>
  .pb-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;font-weight:700;font-size:12px;color:var(--soft)}
  .pb-note{font-size:12.5px;color:var(--soft);line-height:1.5;margin-bottom:12px}
  .pb-note b{color:var(--text)}
  .pb-mode{display:flex;gap:4px;padding:4px;margin-bottom:12px;border-radius:999px;background:#fff;border:1px solid var(--line)}
  .pb-mode a{flex:1;text-align:center;padding:9px;border-radius:999px;font-weight:800;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--soft)}
  .pb-mode a.on{background:#101010;color:#fff}
  .pb-frame{background:#fff;border-radius:18px;padding:12px;border:1px solid var(--line);box-shadow:0 10px 26px rgba(15,24,48,.08)}
  .pb-frame svg{display:block;width:100%;height:auto}
  .pb-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:14px}
  .pb-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px;border-radius:12px;border:none;font-family:'Poppins',sans-serif;font-weight:800;font-size:12.5px;letter-spacing:.03em;text-transform:uppercase;cursor:pointer}
  .pb-btn.dark{background:#101010;color:#fff}
  .pb-btn.light{background:#fff;color:#101010;border:1px solid var(--line)}
  .pb-edit{display:block;text-align:center;margin-top:12px;font-weight:700;font-size:12.5px;color:var(--blue)}
  @media print{
    .app-top,.app-tabs,.pb-back,.pb-note,.pb-actions,.pb-edit,.pb-mode{display:none!important}
    .app-main{padding:0!important}
    .pb-frame{border:none;box-shadow:none;padding:0;width:70mm}
  }
</style>
@endverbatim
@endpush

@section('content')
<a class="pb-back" href="{{ route('gtr.account.race.detail', $reg) }}">← Detail Race</a>

<p class="pb-note">
  @if($isDefault)
    Target belum kamu simpan — pace band ini memakai estimasi bawaan <b>{{ $hm($target) }}</b>.
  @else
    Target finish kamu: <b>{{ $hm($target) }}</b>.
  @endif
  Simpan ke galeri untuk wallpaper HP, atau cetak lalu tempel di lengan.
</p>

<div class="pb-mode">
  <a href="{{ route('gtr.account.race.paceband', [$reg, 'mode' => 'pos']) }}" @class(['on' => $mode === 'pos'])>Per Pos</a>
  <a href="{{ route('gtr.account.race.paceband', [$reg, 'mode' => 'km']) }}" @class(['on' => $mode === 'km'])>Per KM</a>
</div>

<div class="pb-frame">
  <svg id="pace-band" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {{ $W }} {{ $H }}" width="{{ $W }}" height="{{ $H }}" font-family="{{ $font }}">
    <rect width="{{ $W }}" height="{{ $H }}" fill="#ffffff"/>
    {{-- header --}}
    <rect width="{{ $W }}" height="{{ $head - 14 }}" fill="#101010"/>
    <rect y="{{ $head - 16 }}" width="{{ $W }}" height="3" fill="#E53935"/>
    <text x="18" y="30" fill="#FF8A80" font-size="10" font-weight="700" letter-spacing="1.6">GERUNG TRAIL RUN 2026</text>
    <text x="18" y="66" fill="#ffffff" font-size="34" font-weight="900">{{ $cat->distance }}</text>
    <text x="18" y="86" fill="#B4B4B4" font-size="11" font-weight="700">{{ \Illuminate\Support\Str::limit($reg->full_name, 26) }}{{ $reg->bib_number ? ' · BIB ' . $reg->bib_number : '' }}</text>
    <text x="{{ $W - 18 }}" y="30" fill="#B4B4B4" font-size="9" font-weight="700" text-anchor="end" letter-spacing="1.4">TARGET</text>
    <text x="{{ $W - 18 }}" y="66" fill="#ffffff" font-size="34" font-weight="900" text-anchor="end">{{ $hm($target) }}</text>
    <text x="{{ $W - 18 }}" y="86" fill="#B4B4B4" font-size="11" font-weight="700" text-anchor="end">Start {{ $clock($plan[0]['eta']) }} WITA</text>

    @if($mode === 'km')
      @php $y = $head; @endphp
      <text x="18" y="{{ $y + 14 }}" fill="#9A9A9A" font-size="8.5" font-weight="700" letter-spacing="1">KM</text>
      <text x="72" y="{{ $y + 14 }}" fill="#9A9A9A" font-size="8.5" font-weight="700" letter-spacing="1">PACE</text>
      <text x="140" y="{{ $y + 14 }}" fill="#9A9A9A" font-size="8.5" font-weight="700" letter-spacing="1">ELEVASI</text>
      <text x="{{ $W - 18 }}" y="{{ $y + 14 }}" fill="#9A9A9A" font-size="8.5" font-weight="700" letter-spacing="1" text-anchor="end">JAM</text>
      @php $y += $kmHead; @endphp
      @foreach($splits as $i => $s)
        @if($i % 2 === 0)<rect x="10" y="{{ $y }}" width="{{ $W - 20 }}" height="{{ $kmH }}" rx="7" fill="#F5F5F5"/>@endif
        <rect x="18" y="{{ $y + 8 }}" width="40" height="18" rx="5" fill="{{ $tone($s) }}"/>
        <text x="38" y="{{ $y + 21 }}" fill="#ffffff" font-size="11" font-weight="900" text-anchor="middle">{{ $s['km'] }}</text>
        <text x="72" y="{{ $y + 22 }}" fill="#101010" font-size="14" font-weight="900">{{ $mmss($s['pace']) }}</text>
        <text x="140" y="{{ $y + 15 }}" fill="#DC2626" font-size="9" font-weight="700">↗{{ $s['gain'] }}</text>
        <text x="140" y="{{ $y + 27 }}" fill="#2563EB" font-size="9" font-weight="700">↘{{ $s['loss'] }}</text>
        @php
          $tags = collect($s['points'])->pluck('code')->implode(' ');
          $hz = collect($s['hazards'] ?? [])->pluck('icon')->unique()->implode('');
        @endphp
        @if($tags || $hz)
          <text x="180" y="{{ $y + 22 }}" fill="#F97316" font-size="10" font-weight="900">{{ $tags }}{{ $hz ? ' ' . $hz : '' }}</text>
        @endif
        <text x="{{ $W - 18 }}" y="{{ $y + 22 }}" fill="#101010" font-size="14" font-weight="900" text-anchor="end">{{ $clock($s['eta']) }}</text>
        @php $y += $kmH; @endphp
      @endforeach
    @else
    {{-- kolom --}}
    @php $y = $head; @endphp
    @foreach($plan as $i => $row)
      @if($i > 0 && $row['segment'])
        @php $s = $row['segment']; @endphp
        <line x1="45" y1="{{ $y }}" x2="45" y2="{{ $y + $segH }}" stroke="#D4D4D4" stroke-width="2" stroke-dasharray="3 3"/>
        <text x="60" y="{{ $y + 15 }}" fill="#6B6B6B" font-size="10" font-weight="700">{{ number_format($s['dist'], 1) }} km   ↗{{ $s['gain'] }} ↘{{ $s['loss'] }} m   {{ $s['label'] }}</text>
        @php $y += $segH; @endphp
      @endif
      <rect x="10" y="{{ $y + 3 }}" width="{{ $W - 20 }}" height="{{ $rowH - 6 }}" rx="9" fill="#F5F5F5"/>
      <rect x="18" y="{{ $y + 13 }}" width="54" height="20" rx="5" fill="{{ $color[$row['type']] ?? '#101010' }}"/>
      <text x="45" y="{{ $y + 27 }}" fill="#ffffff" font-size="10" font-weight="900" text-anchor="middle">{{ $row['code'] }}</text>
      <text x="82" y="{{ $y + 22 }}" fill="#101010" font-size="12" font-weight="800">{{ \Illuminate\Support\Str::limit($row['type'] === 'start' || $row['type'] === 'finish' ? $row['title'] : ($row['title'] ?: $row['code']), 18) }}</text>
      <text x="82" y="{{ $y + 36 }}" fill="#8A8A8A" font-size="10" font-weight="700">KM {{ rtrim(rtrim(number_format($row['km'], 1), '0'), '.') }}{{ $i > 0 ? '  ·  +' . $hm($row['elapsed']) : '' }}</text>
      <text x="{{ $row['cutoff'] !== null ? $W - 86 : $W - 22 }}" y="{{ $y + 30 }}" fill="#101010" font-size="18" font-weight="900" text-anchor="end">{{ $clock($row['eta']) }}</text>
      @if($row['cutoff'] !== null)
        @php $buf = $row['eta'] !== null ? $row['cutoff'] - $row['eta'] : null; @endphp
        <rect x="{{ $W - 80 }}" y="{{ $y + 12 }}" width="60" height="22" rx="6" fill="{{ $buf === null ? '#EEE' : ($buf >= 15 ? '#DCFCE7' : ($buf >= 0 ? '#FEF3C7' : '#FEE2E2')) }}"/>
        <text x="{{ $W - 50 }}" y="{{ $y + 21 }}" fill="#555" font-size="7" font-weight="700" text-anchor="middle" letter-spacing=".6">COT</text>
        <text x="{{ $W - 50 }}" y="{{ $y + 31 }}" fill="#101010" font-size="10" font-weight="900" text-anchor="middle">{{ $clock($row['cutoff']) }}</text>
      @endif
      @php $y += $rowH; @endphp
    @endforeach
    @endif

    <text x="{{ $W / 2 }}" y="{{ $H - 16 }}" fill="#9A9A9A" font-size="9" font-weight="700" text-anchor="middle">Estimasi dari jarak &amp; elevasi · runger · tetap ikuti arahan marshal</text>
  </svg>
</div>

<div class="pb-actions">
  <button type="button" class="pb-btn dark" id="pb-download">Simpan Gambar</button>
  <button type="button" class="pb-btn light" onclick="window.print()">Cetak</button>
</div>
<a class="pb-edit" href="{{ route('gtr.account.category', $cat) }}#race-plan">Ubah target di Estimasi →</a>
@endsection

@push('scripts')
<script>
  (function () {
    var btn = document.getElementById('pb-download');
    btn.addEventListener('click', function () {
      var svg = document.getElementById('pace-band');
      var w = svg.viewBox.baseVal.width, h = svg.viewBox.baseVal.height, scale = 3;
      var data = new XMLSerializer().serializeToString(svg);
      var img = new Image();
      img.onload = function () {
        var c = document.createElement('canvas');
        c.width = w * scale; c.height = h * scale;
        var ctx = c.getContext('2d');
        ctx.scale(scale, scale);
        ctx.drawImage(img, 0, 0, w, h);
        c.toBlob(function (blob) {
          var a = document.createElement('a');
          a.href = URL.createObjectURL(blob);
          a.download = @json('pace-band-gtr2026-' . \Illuminate\Support\Str::slug($cat->distance) . '-' . $mode . '.png');
          document.body.appendChild(a); a.click(); a.remove();
          setTimeout(function () { URL.revokeObjectURL(a.href); }, 2000);
        }, 'image/png');
      };
      img.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(data);
    });
  })();
</script>
@endpush
