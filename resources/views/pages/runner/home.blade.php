@extends('layouts.runner-app')
@section('title', 'Home — Runger')
@section('app-title', 'Runger')

@push('styles')
@verbatim
<style>
  /* blue Runger welcome banner */
  .welcome{
    position:relative;overflow:hidden;border-radius:20px;padding:22px 20px;margin-bottom:20px;color:#fff;
    background:
      radial-gradient(360px 200px at 92% -25%, rgba(76,125,242,.55), transparent 60%),
      radial-gradient(300px 220px at 0% 130%, rgba(226,240,84,.16), transparent 62%),
      linear-gradient(125deg,#2A4FC8 0%,#1B3FAE 44%,#0F2680 100%);
    box-shadow:0 16px 40px rgba(27,63,174,.42);
  }
  .welcome::after{content:'';position:absolute;top:-44px;right:-30px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.1)}
  .welcome-logo{height:26px;width:auto;margin-bottom:14px;position:relative;z-index:1}
  .welcome h1{font-family:'Archivo',sans-serif;font-weight:900;font-size:25px;line-height:1.05;position:relative;z-index:1}
  .welcome p{font-size:13px;opacity:.92;margin-top:6px;max-width:32ch;position:relative;z-index:1}
  .welcome-actions{display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-top:16px;position:relative;z-index:1}
  .welcome .pill{display:inline-flex;align-items:center;gap:7px;background:rgba(0,0,0,.2);border:1px solid rgba(255,255,255,.28);padding:8px 13px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:700;font-size:11.5px}
  .welcome-btn{display:inline-flex;align-items:center;gap:7px;background:#fff;color:#0F2680;font-family:'Archivo',sans-serif;font-weight:800;font-size:11.5px;letter-spacing:.03em;text-transform:uppercase;padding:8px 15px;border-radius:999px;transition:transform .15s, box-shadow .2s;box-shadow:0 6px 16px rgba(0,0,0,.18)}
  .welcome-btn:hover{transform:translateY(-1px)}

  .home-h{font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--blue);margin:6px 2px 14px}

  /* race category cards (GTR) */
  .rcat{
    position:relative;border-radius:18px;overflow:hidden;min-height:320px;margin-bottom:16px;
    display:flex;flex-direction:column;justify-content:flex-end;
    background-size:cover;background-position:center top;background-color:#161616;
    border:1px solid var(--line);box-shadow:0 12px 30px rgba(15,24,48,.22)}
  .rcat::before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.18) 0%,rgba(0,0,0,.12) 36%,rgba(0,0,0,.92) 100%)}
  .rcat-top{position:absolute;top:13px;left:13px;right:13px;z-index:2;display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
  .rcat-tag{display:inline-flex;align-items:center;gap:7px;padding:6px 11px;border-radius:999px;background:rgba(0,0,0,.45);border:1px solid rgba(255,255,255,.2);backdrop-filter:blur(4px);font-family:'Archivo',sans-serif;font-weight:800;font-size:10.5px;text-transform:uppercase;color:#fff}
  .rcat-tag .dot{width:8px;height:8px;border-radius:50%}
  .rcat-gtr{height:30px;width:auto;border-radius:6px;box-shadow:0 3px 10px rgba(0,0,0,.4)}
  .rcat-body{position:relative;z-index:2;padding:16px}
  .rcat-name{font-family:'Archivo',sans-serif;font-weight:700;font-size:12.5px;letter-spacing:.02em;color:rgba(255,255,255,.85);text-transform:uppercase}
  .rcat-dist{font-family:'Archivo',sans-serif;font-weight:900;font-size:40px;line-height:.9;color:#fff;margin-top:2px}
  .rcat-meta{display:flex;gap:16px;margin-top:8px}
  .rcat-meta .m .l{font-family:'Archivo',sans-serif;font-size:9px;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.6);font-weight:700}
  .rcat-meta .m .v{font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;color:#fff;margin-top:2px}
  .rcat-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:16px}
  .rcat-price .eb{font-family:'Archivo',sans-serif;font-weight:900;font-size:17px;color:#fff}
  .rcat-price .nm{font-size:11.5px;color:rgba(255,255,255,.5);text-decoration:line-through;text-decoration-color:var(--blue-bright);margin-left:6px}
  .rcat-done{font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#22c55e;display:inline-flex;align-items:center;gap:6px}
</style>
@endverbatim
@endpush

@section('content')
<div class="welcome">
  <img class="welcome-logo" src="{{ asset('logo-white.png') }}" alt="Runger">
  <h1>Halo, {{ $runner->first_name }} 👋</h1>
  <p>Selamat datang di portal Komunitas Runger (Runner Gerung).</p>
  <div class="welcome-actions">
    <span class="pill">🏃 {{ count($registeredIds) }} pendaftaran aktif</span>
    <a class="welcome-btn" href="{{ route('gtr') }}">Halaman Event GTR →</a>
  </div>
</div>

<div class="home-h">Pilih Kategori Race</div>

@foreach($categories as $c)
  <article class="rcat" style="background-image:url('{{ $c->header_url }}')">
    <div class="rcat-top">
      <span class="rcat-tag"><span class="dot" style="background:{{ $c->color }}"></span>{{ $c->tag }}</span>
      <img class="rcat-gtr" src="{{ asset('assets/gtr/logo.jpeg') }}" alt="Gerung Trail Run">
    </div>
    <div class="rcat-body">
      <div class="rcat-name">{{ $c->name }}</div>
      <div class="rcat-dist">{{ $c->distance }}</div>
      <div class="rcat-meta">
        <div class="m"><div class="l">Elevation</div><div class="v">{{ $c->elevation_gain ?: '-' }}</div></div>
        <div class="m"><div class="l">Cut-Off</div><div class="v">{{ $c->cut_off_time ?: '-' }}</div></div>
      </div>
      <div class="rcat-foot">
        <div class="rcat-price"><span class="eb">{{ $c->early_bird_formatted }}</span><span class="nm">{{ $c->normal_formatted }}</span></div>
        @if(in_array($c->id, $registeredIds))
          <span class="rcat-done">✓ Terdaftar</span>
        @else
          <a href="{{ route('gtr.register.form', $c) }}" class="btn-blue">Daftar</a>
        @endif
      </div>
    </div>
  </article>
@endforeach
@endsection
