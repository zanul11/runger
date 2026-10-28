@extends('layouts.runner-app')
@section('title', 'Home — Runger')
@section('app-title', 'Home')

@push('styles')
@verbatim
<style>
  /* event header — banner GTR (foto event + countdown) */
  .ev{
    display:block;position:relative;overflow:hidden;isolation:isolate;border-radius:24px;margin-bottom:20px;color:#fff;
    background:#0A0A0A;box-shadow:0 20px 46px rgba(10,10,10,.42);
  }
  .ev-photo{position:absolute;inset:0;z-index:-2;background-size:cover;background-position:center;transform:scale(1.04)}
  .ev::before{content:'';position:absolute;inset:0;z-index:-1;
    background:linear-gradient(180deg,rgba(10,10,10,.35) 0%,rgba(10,10,10,.55) 45%,rgba(10,10,10,.94) 100%),
               radial-gradient(260px 200px at 100% 0%,rgba(229,57,53,.45),transparent 70%)}
  .ev-in{padding:16px 18px 18px}
  .ev-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .ev-hi{display:inline-flex;align-items:center;gap:9px;min-width:0;padding:5px 12px 5px 5px;border-radius:999px;
    background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px)}
  .ev-ava{width:26px;height:26px;flex-shrink:0;border-radius:50%;display:grid;place-items:center;background:var(--red);font-weight:900;font-size:12px}
  .ev-hi span{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .ev-hi b{font-weight:800}
  .ev-body{margin-top:56px}
  .ev-k{display:inline-flex;align-items:center;gap:7px;font-weight:800;font-size:9.5px;letter-spacing:.16em;text-transform:uppercase;color:#FF8A80}
  .ev-k::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--red);box-shadow:0 0 0 3px rgba(229,57,53,.3)}
  .ev h1{font-weight:900;font-size:27px;line-height:1.02;letter-spacing:-.01em;margin-top:7px;text-transform:uppercase}
  .ev-meta{display:flex;flex-direction:column;gap:5px;margin-top:10px}
  .ev-meta span{display:flex;align-items:flex-start;gap:7px;font-size:12px;line-height:1.4;color:rgba(255,255,255,.85)}
  .ev-meta svg{width:14px;height:14px;flex-shrink:0;margin-top:1px;color:#FF8A80}
  .ev-wx{margin-top:10px;padding:7px 11px!important;font-size:11px!important;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(8px)}
  .ev-cd{display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-top:16px}
  .ev-cd div{text-align:center;padding:9px 0 8px;border-radius:13px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);backdrop-filter:blur(8px)}
  .ev-cd b{display:block;font-weight:900;font-size:21px;line-height:1;font-variant-numeric:tabular-nums}
  .ev-cd small{display:block;margin-top:4px;font-weight:700;font-size:8px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.6)}
  .ev-row{display:flex;align-items:stretch;gap:10px;margin-top:14px}
  .ev-stat{display:flex;align-items:center;gap:10px;padding:8px 13px;border-radius:14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.16)}
  .ev-stat .n{font-weight:900;font-size:22px;line-height:1}
  .ev-stat .l{font-weight:700;font-size:8.5px;letter-spacing:.08em;text-transform:uppercase;opacity:.75;line-height:1.15}
  .ev-btn{margin-left:auto;display:inline-flex;align-items:center;gap:8px;background:var(--red);color:#fff;
    font-weight:800;font-size:11px;letter-spacing:.04em;text-transform:uppercase;
    padding:0 16px;border-radius:14px;box-shadow:0 8px 20px rgba(229,57,53,.4);transition:transform .15s}
  .ev-btn .arr{display:inline-flex;width:20px;height:20px;align-items:center;justify-content:center;background:rgba(255,255,255,.22);border-radius:50%;font-size:12px}
  .ev-btn:active{transform:translateY(1px)}

  /* section header — beda gaya dari kartu kategori (light, accent biru) */
  .home-head{position:relative;display:flex;align-items:center;gap:13px;margin:14px 2px 16px;padding:2px 0 2px 1px}
  .home-head .hh-bar{width:5px;align-self:stretch;min-height:36px;border-radius:4px;background:linear-gradient(180deg,var(--blue-bright),var(--blue-deep));box-shadow:0 4px 12px rgba(27,63,174,.35)}
  .home-head .hh-txt{display:flex;flex-direction:column;gap:3px;min-width:0}
  .home-head .hh-k{display:inline-flex;align-items:center;gap:7px;font-family:'Poppins',sans-serif;font-weight:800;font-size:9.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--blue)}
  .home-head .hh-k::before{content:'';width:14px;height:2px;border-radius:2px;background:var(--blue)}
  .home-head .hh-t{font-family:'Poppins',sans-serif;font-weight:900;font-size:21px;letter-spacing:-.01em;color:var(--text);line-height:1}
  .home-head .hh-count{margin-left:auto;display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border-radius:999px;
    background:rgba(63,98,216,.1);border:1px solid rgba(63,98,216,.26);
    font-family:'Poppins',sans-serif;font-weight:800;font-size:10.5px;letter-spacing:.03em;text-transform:uppercase;color:var(--blue);white-space:nowrap}
  .home-head .hh-count .d{width:7px;height:7px;border-radius:50%;background:var(--blue-bright);box-shadow:0 0 8px var(--blue-bright)}

  /* race category cards (GTR) — kartu horizontal modern (memanjang) */
  .rcat-grid{display:flex;flex-direction:column;gap:14px}
  .rcat{
    position:relative;display:flex;min-height:192px;border-radius:20px;overflow:hidden;
    background:linear-gradient(135deg,#171b27 0%,#0e1018 100%);
    border:1px solid rgba(255,255,255,.07);box-shadow:0 14px 32px rgba(8,12,24,.42)}
  .rcat-photo{position:relative;width:142px;flex-shrink:0;background-size:cover;background-position:center;background-color:#10131c}
  .rcat-photo::after{content:'';position:absolute;inset:0;
    background:linear-gradient(90deg,rgba(0,0,0,.22) 0%,transparent 36%,transparent 70%,rgba(14,16,24,.94) 100%)}
  .rcat-photo .bar{position:absolute;left:0;top:0;bottom:0;width:4px;z-index:2;background:var(--rcat-accent,#4C7DF2)}
  .rcat-tag{position:absolute;top:11px;left:11px;z-index:3;display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;
    background:rgba(10,14,28,.55);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(8px);
    font-family:'Poppins',sans-serif;font-weight:800;font-size:9px;letter-spacing:.05em;text-transform:uppercase;color:#fff}
  .rcat-tag .dot{width:7px;height:7px;border-radius:50%;background:var(--rcat-accent,#4C7DF2);box-shadow:0 0 8px var(--rcat-accent,#4C7DF2)}

  .rcat-info{flex:1;min-width:0;padding:15px 16px;display:flex;flex-direction:column;position:relative;overflow:hidden;
    background:
      repeating-linear-gradient(125deg, rgba(255,255,255,.03) 0 1px, transparent 1px 11px),
      radial-gradient(165px 130px at 118% -14%, color-mix(in srgb, var(--rcat-accent,#4C7DF2) 45%, transparent), transparent 66%),
      radial-gradient(140px 130px at -10% 120%, color-mix(in srgb, var(--rcat-accent,#4C7DF2) 22%, transparent), transparent 70%),
      linear-gradient(135deg,#1a1f2e 0%,#0d0f17 100%)}
  /* glow aksen pojok kanan-atas */
  .rcat-info::after{content:'';position:absolute;top:0;right:0;width:74px;height:74px;
    background:radial-gradient(circle at 70% 30%, color-mix(in srgb, var(--rcat-accent,#4C7DF2) 55%, transparent), transparent 62%);
    filter:blur(2px);pointer-events:none}
  .rcat-info > *{position:relative;z-index:1}
  .rcat-head{display:flex;justify-content:space-between;align-items:center;gap:8px}
  .rcat-name{display:inline-flex;align-items:center;gap:8px;font-family:'Poppins',sans-serif;font-weight:800;font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#fff;opacity:.9;min-width:0;overflow:hidden}
  .rcat-name::before{content:'';width:16px;height:2px;border-radius:2px;background:var(--rcat-accent,#4C7DF2);flex-shrink:0}
  .rcat-gtr{height:24px;width:auto;border-radius:6px;flex-shrink:0;box-shadow:0 3px 10px rgba(0,0,0,.4)}
  .rcat-dist{font-family:'Poppins',sans-serif;font-weight:900;font-size:42px;line-height:.85;color:#fff;margin-top:7px}
  .rcat-meta{display:flex;gap:8px;margin-top:11px}
  .rcat-meta .m{background:color-mix(in srgb, var(--rcat-accent,#4C7DF2) 9%, rgba(255,255,255,.05));border:1px solid color-mix(in srgb, var(--rcat-accent,#4C7DF2) 26%, rgba(255,255,255,.1));border-left:2px solid var(--rcat-accent,#4C7DF2);padding:6px 10px;border-radius:11px;min-width:0}
  .rcat-meta .m .l{font-family:'Poppins',sans-serif;font-size:7.5px;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.55);font-weight:700;white-space:nowrap}
  .rcat-meta .m .v{font-family:'Poppins',sans-serif;font-weight:800;font-size:12.5px;color:#fff;margin-top:2px;white-space:nowrap}
  .rcat-foot{margin-top:auto;padding-top:14px;display:flex;justify-content:space-between;align-items:flex-end;gap:10px}
  .rcat-price{display:flex;flex-direction:column;gap:1px;min-width:0}
  .rcat-price .lab{font-family:'Poppins',sans-serif;font-size:7.5px;letter-spacing:.1em;text-transform:uppercase;color:color-mix(in srgb, var(--rcat-accent,#4C7DF2) 65%, #fff);font-weight:800}
  .rcat-price .eb{font-family:'Poppins',sans-serif;font-weight:900;font-size:18px;color:#fff;line-height:1}
  .rcat-price .nm{font-size:10.5px;color:rgba(255,255,255,.45);text-decoration:line-through;text-decoration-color:var(--rcat-accent,#4C7DF2);margin-left:5px}
  .rcat-btn{display:inline-flex;align-items:center;gap:7px;padding:10px 14px;border-radius:999px;flex-shrink:0;
    background:var(--rcat-accent,#4C7DF2);color:#fff;font-family:'Poppins',sans-serif;font-weight:800;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;
    box-shadow:0 8px 18px rgba(0,0,0,.35);transition:transform .15s;white-space:nowrap}
  .rcat-btn .arr{display:inline-flex;width:19px;height:19px;align-items:center;justify-content:center;background:rgba(255,255,255,.22);border-radius:50%;font-size:11px}
  .rcat-btn:active{transform:translateY(1px)}
  /* seluruh kartu bisa diklik ke detail; tombol Daftar tetap di atasnya */
  .rcat-link{position:absolute;inset:0;z-index:4;border-radius:inherit}
  .rcat-btn,.rcat-done{position:relative;z-index:5}
  .rcat-more{margin-top:9px;font-family:'Poppins',sans-serif;font-weight:700;font-size:10.5px;color:color-mix(in srgb, var(--rcat-accent,#4C7DF2) 70%, #fff)}
  .rcat-done{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:999px;flex-shrink:0;
    background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4);
    font-family:'Poppins',sans-serif;font-weight:800;font-size:10.5px;letter-spacing:.03em;text-transform:uppercase;color:#34d27b}
</style>
@endverbatim
@endpush

@section('content')
<a class="ev" href="{{ route('gtr') }}" aria-label="Lihat event Gerung Trail Run">
  <div class="ev-photo" style="background-image:url('{{ $gtrSetting?->header_url ?? asset('assets/gtr/gallery/ari.jpeg') }}')"></div>
  <div class="ev-in">
    <div class="ev-top">
      <div class="ev-hi">
        <span class="ev-ava">{{ strtoupper(mb_substr($runner->first_name ?: 'R', 0, 1)) }}</span>
        <span>Halo, <b>{{ $runner->first_name }}</b> 👋</span>
      </div>
    </div>

    <div class="ev-body">
      <span class="ev-k">{{ $gtrSetting->eyebrow ?? '1st Edition · Keteri Hill' }}</span>
      <h1>{{ $gtrSetting->title ?? 'Gerung Trail Run 2026' }}</h1>
      <div class="ev-meta">
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>{{ $gtrSetting->date_text ?? 'Minggu, 29 November 2026' }}</span>
        <span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $gtrSetting->location_text ?? 'Bukit Keteri, Gerung — Lombok Barat' }}</span>
      </div>
      @include('pages.partials.gtr-weather', ['style' => 'chip', 'class' => 'ev-wx'])

      <div class="ev-cd" id="ev-cd">
        <div><b data-u="d">--</b><small>Hari</small></div>
        <div><b data-u="h">--</b><small>Jam</small></div>
        <div><b data-u="m">--</b><small>Menit</small></div>
        <div><b data-u="s">--</b><small>Detik</small></div>
      </div>

      <div class="ev-row">
        <div class="ev-stat">
          <span class="n">{{ count($registeredIds) }}</span>
          <span class="l">Pendaftaran<br>Aktif</span>
        </div>
        <span class="ev-btn">Info Event <span class="arr">→</span></span>
      </div>
    </div>
  </div>
</a>

<div class="home-head">
  <span class="hh-bar"></span>
  <div class="hh-txt">
    <span class="hh-k">Gerung Trail Run 2026</span>
    <span class="hh-t">Pilih Kategori Race</span>
  </div>
  <span class="hh-count"><span class="d"></span>{{ $categories->count() }} Kategori</span>
</div>

<div class="rcat-grid">
@foreach($categories as $c)
  <article class="rcat" style="--rcat-accent:{{ $c->color ?: '#4C7DF2' }}">
    <a class="rcat-link" href="{{ route('gtr.account.category', $c) }}" aria-label="Detail kategori {{ $c->distance }}"></a>
    <div class="rcat-photo" style="background-image:url('{{ $c->header_url }}')">
      <span class="bar"></span>
      <span class="rcat-tag"><span class="dot"></span>{{ $c->tag }}</span>
    </div>
    <div class="rcat-info">
      <div class="rcat-head">
        <div class="rcat-name">{{ $c->name }}</div>
        <img class="rcat-gtr" src="{{ asset('assets/gtr/logo.jpeg') }}" alt="GTR">
      </div>
      <div class="rcat-dist">{{ $c->distance }}</div>
      <div class="rcat-meta">
        <div class="m"><div class="l">Elevation</div><div class="v">{{ $c->elevation_gain ?: '-' }}</div></div>
        <div class="m"><div class="l">Cut-Off</div><div class="v">{{ $c->cut_off_time ?: '-' }}</div></div>
      </div>
      <span class="rcat-more">Detail rute &amp; estimasi →</span>
      <div class="rcat-foot">
        <div class="rcat-price">
          <span class="lab">{{ $c->currentPriceLabel() }}</span>
          @if($c->earlyBirdActive())
            <span><span class="eb">{{ $c->early_bird_formatted }}</span><span class="nm">{{ $c->normal_formatted }}</span></span>
          @else
            <span><span class="eb">{{ $c->current_price_formatted }}</span></span>
          @endif
        </div>
        @if(in_array($c->id, $registeredIds))
          <span class="rcat-done">✓ Terdaftar</span>
        @elseif(\App\Models\GtrSetting::registrationClosed())
          <span class="rcat-done">Pendaftaran ditutup</span>
        @else
          <a href="{{ route('gtr.register.form', $c) }}" class="rcat-btn">Daftar <span class="arr">→</span></a>
        @endif
      </div>
    </div>
  </article>
@endforeach
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var box = document.getElementById('ev-cd');
    if (!box) return;
    // race start: Minggu 29 Nov 2026, 05:30 WITA (UTC+8) — sama dengan countdown halaman GTR
    var target = Date.UTC(2026, 10, 29, 5, 30) - 8 * 3600000;
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
