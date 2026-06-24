@extends('layouts.runner-app')
@section('title', 'Home — Runger')
@section('app-title', 'Runger')

@push('styles')
@verbatim
<style>
  /* greeting header — banner sapaan user */
  .welcome{
    position:relative;overflow:hidden;border-radius:22px;padding:20px;margin-bottom:20px;color:#fff;
    background:
      radial-gradient(420px 230px at 88% -32%, rgba(108,150,255,.6), transparent 60%),
      radial-gradient(320px 250px at -6% 132%, rgba(63,98,216,.5), transparent 62%),
      linear-gradient(135deg,#2A4FC8 0%,#1B3FAE 46%,#0F2680 100%);
    box-shadow:0 18px 44px rgba(15,38,128,.46);
  }
  .welcome::before{content:'';position:absolute;top:-66px;right:-46px;width:188px;height:188px;border-radius:50%;border:1.5px solid rgba(255,255,255,.16)}
  .welcome::after{content:'';position:absolute;top:-30px;right:10px;width:104px;height:104px;border-radius:50%;background:rgba(255,255,255,.08)}
  .wel-top{position:relative;z-index:1;display:flex;align-items:center;gap:13px}
  .wel-ava{width:48px;height:48px;flex-shrink:0;border-radius:15px;display:flex;align-items:center;justify-content:center;
    background:rgba(255,255,255,.17);border:1px solid rgba(255,255,255,.32);backdrop-filter:blur(6px);
    font-family:'Poppins',sans-serif;font-weight:900;font-size:22px}
  .wel-hi{min-width:0;flex:1}
  .wel-hi .k{font-family:'Poppins',sans-serif;font-weight:800;font-size:9.5px;letter-spacing:.15em;text-transform:uppercase;opacity:.82}
  .wel-hi h1{font-family:'Poppins',sans-serif;font-weight:900;font-size:23px;line-height:1.04;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .wel-logo{height:22px;width:auto;opacity:.95;flex-shrink:0;align-self:flex-start}
  .wel-sub{position:relative;z-index:1;font-size:12.5px;opacity:.9;margin-top:13px;max-width:42ch;line-height:1.5}
  .wel-row{position:relative;z-index:1;display:flex;align-items:stretch;gap:12px;margin-top:16px}
  .wel-stat{display:flex;align-items:center;gap:11px;padding:9px 15px;border-radius:14px;background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.22)}
  .wel-stat .n{font-family:'Poppins',sans-serif;font-weight:900;font-size:26px;line-height:1}
  .wel-stat .l{font-family:'Poppins',sans-serif;font-weight:700;font-size:8.5px;letter-spacing:.08em;text-transform:uppercase;opacity:.82;line-height:1.15}
  .wel-btn{margin-left:auto;display:inline-flex;align-items:center;gap:8px;background:#fff;color:#0F2680;
    font-family:'Poppins',sans-serif;font-weight:800;font-size:11px;letter-spacing:.03em;text-transform:uppercase;
    padding:0 16px;border-radius:14px;box-shadow:0 8px 20px rgba(0,0,0,.24);transition:transform .15s}
  .wel-btn .arr{display:inline-flex;width:20px;height:20px;align-items:center;justify-content:center;background:rgba(15,38,128,.12);border-radius:50%;font-size:12px}
  .wel-btn:active{transform:translateY(1px)}

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
  .rcat-done{display:inline-flex;align-items:center;gap:6px;padding:9px 14px;border-radius:999px;flex-shrink:0;
    background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4);
    font-family:'Poppins',sans-serif;font-weight:800;font-size:10.5px;letter-spacing:.03em;text-transform:uppercase;color:#34d27b}
</style>
@endverbatim
@endpush

@section('content')
<div class="welcome">
  <div class="wel-top">
    <div class="wel-ava">{{ strtoupper(mb_substr($runner->first_name ?: 'R', 0, 1)) }}</div>
    <div class="wel-hi">
      <span class="k">Selamat Datang 👋</span>
      <h1>Halo, {{ $runner->first_name }}</h1>
    </div>
    <img class="wel-logo" src="{{ asset('logo-white.png') }}" alt="Runger">
  </div>
  <p class="wel-sub">Portal peserta Komunitas Runger — daftar &amp; kelola race Gerung Trail Run kamu di sini.</p>
  <div class="wel-row">
    <div class="wel-stat">
      <span class="n">{{ count($registeredIds) }}</span>
      <span class="l">Pendaftaran<br>Aktif</span>
    </div>
    <a class="wel-btn" href="{{ route('gtr') }}">Event GTR <span class="arr">→</span></a>
  </div>
</div>

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
      <div class="rcat-foot">
        <div class="rcat-price">
          <span class="lab">Early Bird</span>
          <span><span class="eb">{{ $c->early_bird_formatted }}</span><span class="nm">{{ $c->normal_formatted }}</span></span>
        </div>
        @if(in_array($c->id, $registeredIds))
          <span class="rcat-done">✓ Terdaftar</span>
        @else
          <a href="{{ route('gtr.register.form', $c) }}" class="rcat-btn">Daftar <span class="arr">→</span></a>
        @endif
      </div>
    </div>
  </article>
@endforeach
</div>
@endsection
