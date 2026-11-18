@extends('layouts.runner-app')
@section('title', 'Home — Runger')

@php
  $hour = (int) now('Asia/Makassar')->format('G');
  $greet = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 18 ? 'Selamat sore' : 'Selamat malam'));
  $paidRegs = $myRegs->where('payment_status', 'paid')->values();
  $unpaidRegs = $myRegs->where('payment_status', '!=', 'paid')->values();
  $main = $paidRegs->first();
  $raceUrl = route('gtr.account.race');
  $resultsOn = $gtrSetting?->results_published && $main?->bib_number;
  $quick = $main ? array_values(array_filter([
    ['Rute', $raceUrl . '#map', '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>'],
    ['Jadwal', route('gtr.account.schedule'), '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
    ['Race Pack', $raceUrl . '#racepack', '<path d="M20 12v9H4v-9"/><rect x="2" y="7" width="20" height="5" rx="1"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>'],
    ['Gear', $raceUrl . '#gear', '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'],
    ['Race Guide', route('gtr.account.guide'), '<path d="M4 19.5V5a2 2 0 0 1 2-2h13v15H6.5a2.5 2.5 0 0 0 0 5H19"/><path d="M9 7h6M9 11h4"/>'],
    $resultsOn
      ? ['Hasil', route('gtr.result', $main->bib_number), '<path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4z"/><path d="M17 5h3v2a3 3 0 0 1-3 3M7 5H4v2a3 3 0 0 0 3 3"/>']
      : (($main->bib_number && config('services.gtr.app_live_tracking'))
        ? ['Live Tracking', route('gtr.track', $main->bib_number), '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8"/>']
        : ['GPX', $raceUrl . '#gpx', '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>']),
  ])) : [];
@endphp

@push('styles')
@verbatim
<style>
  /* sapaan (kartu gelap) */
  .hm-hello{position:relative;overflow:hidden;isolation:isolate;border-radius:24px;padding:18px;color:#fff;
    background:radial-gradient(240px 180px at 100% 0%,rgba(229,57,53,.42),transparent 70%),linear-gradient(150deg,#1b1b1f,#0a0a0c);
    box-shadow:0 16px 36px rgba(10,10,14,.35)}
  .hm-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
  .hm-hi small{display:block;font-size:12.5px;font-weight:500;opacity:.75}
  .hm-hi b{display:block;font-weight:900;font-size:24px;line-height:1.1;margin-top:2px;word-break:break-word}
  .hm-qr{display:inline-flex;align-items:center;gap:7px;flex-shrink:0;padding:10px 14px;border-radius:999px;border:none;cursor:pointer;
    background:#fff;color:#101010;font-family:'Poppins',sans-serif;font-weight:800;font-size:12px}
  .hm-qr svg{width:16px;height:16px}
  .hm-cd-k{margin-top:16px;font-weight:800;font-size:9.5px;letter-spacing:.16em;text-transform:uppercase;color:#FF8A80}
  .hm-cd{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:8px}
  .hm-cd div{text-align:center;padding:9px 0 8px;border-radius:13px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12)}
  .hm-cd b{display:block;font-weight:900;font-size:20px;line-height:1;font-variant-numeric:tabular-nums}
  .hm-cd small{display:block;margin-top:4px;font-weight:700;font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.55)}

  /* kartu runner */
  .hm-runner{display:block;margin-top:12px;padding:16px 18px;border-radius:20px;background:#fff;border:1px solid var(--line);box-shadow:0 8px 22px rgba(15,24,48,.06)}
  .hm-runner .nm{font-weight:900;font-size:18px;color:var(--blue);line-height:1.2}
  .hm-runner .ev{font-size:12.5px;color:var(--soft);margin-top:2px}
  .hm-runner .grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-top:14px}
  .hm-runner .grid small{display:block;font-weight:700;font-size:9px;letter-spacing:.12em;text-transform:uppercase;color:var(--mute)}
  .hm-runner .grid b{display:block;font-weight:900;font-size:19px;color:var(--text);margin-top:2px;font-variant-numeric:tabular-nums;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hm-runner .pk{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:14px;padding-top:12px;border-top:1px solid #F0F2F7;font-size:12.5px;font-weight:700}
  .hm-runner .pk .ok{color:#15803D}
  .hm-runner .pk .wait{color:#B45309}
  .hm-runner .pk .go{color:var(--blue)}

  /* pendaftaran belum lunas */
  .hm-pay{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px;padding:14px 16px;border-radius:18px;background:#FFFBEB;border:1px solid #FDE68A}
  .hm-pay b{display:block;font-size:14px;color:#92400E}
  .hm-pay span{display:block;font-size:12px;color:#B45309;margin-top:2px}
  .hm-pay .go{flex-shrink:0;font-weight:800;font-size:12px;color:#92400E}

  /* quick access */
  .hm-quick{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  .hm-quick a{display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 6px 14px;border-radius:18px;background:#fff;border:1px solid var(--line);
    box-shadow:0 6px 16px rgba(15,24,48,.04);font-weight:700;font-size:11.5px;color:var(--text);text-align:center;transition:transform .15s}
  .hm-quick a:active{transform:scale(.97)}
  .hm-quick svg{width:24px;height:24px;color:var(--text)}

  /* daftar race (belum terdaftar & pendaftaran buka) */
  .hm-cat{display:flex;align-items:center;gap:12px;padding:14px 16px;margin-bottom:10px;border-radius:18px;background:#fff;border:1px solid var(--line);border-left:4px solid var(--cc,#4C7DF2)}
  .hm-cat .d{font-weight:900;font-size:22px;line-height:1;min-width:64px}
  .hm-cat .i{flex:1;min-width:0}
  .hm-cat .i b{display:block;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .hm-cat .i span{display:block;font-size:12px;color:var(--soft);margin-top:1px}
  .hm-cat .btn{flex-shrink:0;padding:10px 14px;border-radius:999px;background:var(--red);color:#fff;font-weight:800;font-size:11.5px;letter-spacing:.03em;text-transform:uppercase}
  .hm-cat .btn.done{background:#DCFCE7;color:#15803D}

  .hm-empty{margin-top:12px;padding:18px;border-radius:18px;background:#fff;border:1px solid var(--line);font-size:13px;color:var(--soft);line-height:1.55}
  .hm-empty b{display:block;font-size:15px;color:var(--text);margin-bottom:4px}

  .hm-web{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:22px;padding:14px 16px;border-radius:18px;
    background:linear-gradient(135deg,var(--blue),var(--blue-deep));color:#fff}
  .hm-web b{display:block;font-size:13.5px}
  .hm-web span{display:block;font-size:11.5px;opacity:.75;margin-top:2px}
  .hm-web .arr{flex-shrink:0;width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.18);font-weight:900}
</style>
@endverbatim
@endpush

@section('content')
<section class="hm-hello">
  <div class="hm-top">
    <div class="hm-hi"><small>{{ $greet }},</small><b>{{ $runner->first_name ?: 'Runner' }}</b></div>
    @if($main)
      <button type="button" class="hm-qr" data-open-qr><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14v.01M14 20h.01M17 20h4v-3"/></svg>My QR</button>
    @endif
  </div>
  <div id="hm-cd-wrap">
    <div class="hm-cd-k">Menuju start {{ $gtrSetting?->title ?: 'Gerung Trail Run 2026' }}</div>
    <div class="hm-cd" id="ev-cd">
      <div><b data-u="d">--</b><small>Hari</small></div>
      <div><b data-u="h">--</b><small>Jam</small></div>
      <div><b data-u="m">--</b><small>Menit</small></div>
      <div><b data-u="s">--</b><small>Detik</small></div>
    </div>
  </div>
</section>

@foreach($paidRegs as $r)
  <a class="hm-runner" href="{{ route('gtr.account.race.detail', $r) }}">
    <div class="nm">{{ $r->full_name }}</div>
    <div class="ev">{{ $gtrSetting?->title ?: 'Gerung Trail Run 2026' }} · {{ $r->category->name ?? '' }}</div>
    <div class="grid">
      <div><small>BIB</small><b>{{ $r->bib_number ?: '—' }}</b></div>
      <div><small>Kategori</small><b>{{ $r->category->distance ?? '-' }}</b></div>
      <div><small>Start</small><b>{{ trim(preg_replace('/\s*WITA\s*/i', '', (string) $r->category?->start_time)) ?: '-' }}</b></div>
    </div>
    <div class="pk">
      @if($r->racepack_collected_at)
        <span class="ok">✓ Race pack sudah diambil</span>
      @else
        <span class="wait">Race pack belum diambil</span>
      @endif
      <span class="go">Detail →</span>
    </div>
  </a>
@endforeach

@foreach($unpaidRegs as $r)
  <a class="hm-pay" href="{{ route('gtr.account.race.detail', $r) }}">
    <div>
      <b>{{ $r->category->distance ?? '' }} · belum lunas</b>
      <span>{{ $closed ? 'Pendaftaran & pembayaran sudah ditutup.' : 'Selesaikan pembayaran untuk mendapat e-ticket & BIB.' }}</span>
    </div>
    @unless($closed)<span class="go">Bayar →</span>@endunless
  </a>
@endforeach

@include('pages.runner.partials.install-card', ['variant' => 'popup'])

@if($dayCard)
  <div style="margin-top:12px">
    @include('pages.runner.partials.race-day-card', ['reg' => $dayReg, 'card' => $dayCard, 'setting' => $gtrSetting, 'compact' => true])
  </div>
@endif

@if($quick)
  <div class="ttl"><h2>Quick Access</h2></div>
  <div class="hm-quick">
    @foreach($quick as $q)
      <a href="{{ $q[1] }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $q[2] !!}</svg>{{ $q[0] }}</a>
    @endforeach
  </div>
@endif

@if($myRegs->isEmpty())
  @if($closed)
    <div class="hm-empty">
      <b>Pendaftaran GTR 2026 sudah ditutup.</b>
      Kamu belum terdaftar di race mana pun. Sampai jumpa di edisi berikutnya!
    </div>
  @else
    <div class="ttl"><h2>Daftar Race</h2></div>
    @foreach($categories as $c)
      <div class="hm-cat" style="--cc:{{ $c->color ?: '#4C7DF2' }}">
        <span class="d">{{ $c->distance }}</span>
        <span class="i"><b>{{ $c->name }}</b><span>{{ $c->current_price_formatted }} · Start {{ $c->start_time ?: '-' }}</span></span>
        <a class="btn" href="{{ route('gtr.register.form', $c) }}">Daftar</a>
      </div>
    @endforeach
  @endif
@endif

<a class="hm-web" href="{{ route('gtr') }}">
  <div><b>Info event lengkap di website</b><span>Kategori, rute, rules, sponsor &amp; galeri</span></div>
  <span class="arr">→</span>
</a>
@endsection

@push('scripts')
<script>
  (function () {
    var box = document.getElementById('ev-cd');
    if (!box) return;
    // jam start kategori peserta (atau start paling awal) — GtrCategory::raceStartAt
    var target = {{ $raceStartMs }};
    if (target - Date.now() <= 0) { document.getElementById('hm-cd-wrap').hidden = true; return; }
    var el = {};
    box.querySelectorAll('[data-u]').forEach(function (b) { el[b.dataset.u] = b; });
    var pad = function (n) { return String(n).padStart(2, '0'); };
    function tick() {
      var diff = Math.max(0, target - Date.now());
      el.d.textContent = pad(Math.floor(diff / 86400000));
      el.h.textContent = pad(Math.floor(diff / 3600000) % 24);
      el.m.textContent = pad(Math.floor(diff / 60000) % 60);
      el.s.textContent = pad(Math.floor(diff / 1000) % 60);
    }
    tick();
    setInterval(tick, 1000);
  })();
</script>
@endpush
