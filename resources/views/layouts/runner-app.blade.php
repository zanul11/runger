<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="theme-color" content="#101010">
<link rel="manifest" href="/manifest.webmanifest">
<link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Runger App">
<title>@yield('title', 'Akun Peserta — Gerung Trail Run')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
@verbatim
<style>
  :root{--red:#E53935;--red-deep:#C62828;--blue:#1B3FAE;--blue-deep:#0F2680;--blue-bright:#3F62D8;--ink:#EEF2FB;--card:#FFFFFF;--line:#E6EAF3;--soft:#5B6378;--mute:#8A92A6;--text:#0F1830}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Poppins',sans-serif;background:#E3E8F4;color:var(--text);-webkit-font-smoothing:antialiased}
  a{color:inherit;text-decoration:none}
  .app{
    max-width:480px;margin:0 auto;min-height:100vh;display:flex;flex-direction:column;position:relative;
    background:
      radial-gradient(540px 320px at 100% 0%, rgba(27,63,174,.08), transparent 60%),
      var(--ink);
  }
  .app-top{
    position:sticky;top:0;z-index:10;overflow:hidden;isolation:isolate;
    display:flex;align-items:center;justify-content:space-between;gap:12px;
    padding:calc(16px + env(safe-area-inset-top)) 18px 18px;
    background:
      radial-gradient(260px 160px at 100% 0%, rgba(229,57,53,.38), transparent 70%),
      linear-gradient(120deg,#1A1A1A 0%,#101010 55%,#0A0A0A 100%);
    border-radius:0 0 24px 24px;
    box-shadow:0 10px 26px rgba(10,10,10,.38);
    transition:padding .25s ease, border-radius .25s ease;
  }
  /* garis kontur topografi + kilau */
  .app-top::before{
    content:'';position:absolute;inset:-40%;z-index:-1;opacity:.14;transform:rotate(-8deg);
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300' viewBox='0 0 300 300'%3E%3Cg fill='none' stroke='%23fff' stroke-width='1.2'%3E%3Cpath d='M-20 60c45-30 90 22 142-4s105-38 188 8'/%3E%3Cpath d='M-20 100c52-26 98 26 150 0s112-34 180 11'/%3E%3Cpath d='M-20 145c41-22 94 30 154 4s108-30 176 13'/%3E%3Cpath d='M-20 190c49-28 101 24 157-2s105-36 173 9'/%3E%3Cpath d='M-20 235c45-21 96 28 148 3s114-32 182 12'/%3E%3C/g%3E%3C/svg%3E");
    background-size:300px 300px
  }
  .app-top::after{content:'';position:absolute;left:0;right:0;bottom:0;height:2px;z-index:-1;background:linear-gradient(90deg,transparent,var(--red) 30%,var(--red) 70%,transparent);opacity:.7}
  .app-top .app-logo{display:flex;align-items:center;gap:10px;line-height:0;min-width:0}
  .app-top img{height:40px;width:auto;filter:drop-shadow(0 4px 12px rgba(0,0,0,.25));transition:height .25s ease}
  .app-top .app-logo .gtr{border-radius:7px;filter:none;box-shadow:0 4px 12px rgba(0,0,0,.45)}
  .app-top .app-logo .sep{width:1px;align-self:stretch;margin:4px 0;background:rgba(255,255,255,.22)}
  .app-top .app-logo .rg{height:26px}
  .app-top .app-title{
    font-family:'Poppins',sans-serif;font-weight:800;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#fff;
    display:inline-flex;align-items:center;gap:8px;padding:8px 13px;border-radius:999px;
    background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(6px);white-space:nowrap
  }
  .app-top .app-title::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--red);box-shadow:0 0 0 3px rgba(229,57,53,.28)}
  .app-top .app-title{min-width:0;overflow:hidden;text-overflow:ellipsis}
  @media (max-width:380px){
    .app-top img{height:32px}
    .app-top .app-logo .rg{height:20px}
    .app-top .app-title{font-size:10px;letter-spacing:.1em;padding:7px 11px}
  }
  .app-top.compact{padding-top:calc(10px + env(safe-area-inset-top));padding-bottom:10px;border-radius:0 0 18px 18px}
  .app-top.compact img{height:28px}
  .app-top.compact .app-logo .rg{height:19px}
  .app-main{flex:1;padding:18px 18px calc(96px + env(safe-area-inset-bottom))}
  .app-ok{background:#DCFCE7;border:1px solid #A7F3D0;color:#15803D;padding:12px 14px;border-radius:12px;font-size:13.5px;margin-bottom:16px}

  .app-tabs{
    position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:480px;z-index:30;
    display:grid;grid-template-columns:repeat(4,1fr);
    background:rgba(255,255,255,.98);backdrop-filter:blur(14px);border-top:1px solid var(--line);
    box-shadow:0 -6px 22px rgba(15,24,48,.07);
    padding-bottom:env(safe-area-inset-bottom);
  }
  .app-tab{display:flex;flex-direction:column;align-items:center;gap:5px;padding:11px 0 13px;color:var(--mute);transition:color .2s}
  .app-tab svg{width:22px;height:22px}
  .app-tab span{font-family:'Poppins',sans-serif;font-size:10px;font-weight:700;letter-spacing:.03em}
  .app-tab.active{color:var(--blue)}

  /* shared content bits */
  .sec-title{font-family:'Poppins',sans-serif;font-weight:900;font-size:26px;line-height:1;letter-spacing:-.01em;margin-bottom:4px;color:var(--text)}
  .sec-sub{color:var(--soft);font-size:13.5px;margin-bottom:18px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px;margin-bottom:14px;box-shadow:0 6px 20px rgba(15,24,48,.05)}
  .card-h{font-family:'Poppins',sans-serif;font-weight:800;font-size:11.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--blue);margin-bottom:14px}
  .badge{font-family:'Poppins',sans-serif;font-weight:700;font-size:10px;letter-spacing:.1em;text-transform:uppercase;padding:5px 11px;border-radius:999px}
  .badge.pending{background:#FEF9C3;color:#A16207;border:1px solid #FDE68A}
  .badge.paid,.badge.confirmed{background:#DCFCE7;color:#15803D;border:1px solid #A7F3D0}
  .badge.cancelled{background:#F3F4F6;color:#6B7280;border:1px solid #E5E7EB}
  .btn-blue{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:var(--blue);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:12px;letter-spacing:.04em;text-transform:uppercase;padding:12px 18px;border-radius:10px;border:none;cursor:pointer;transition:background .2s}
  .btn-blue:hover{background:var(--blue-deep)}
  .empty{text-align:center;color:var(--mute);font-size:13.5px;padding:26px 10px}
  /* info icon (klik untuk tampilkan keterangan) */
  .info-i{position:relative;display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;border-radius:50%;
    background:var(--blue);color:#fff !important;font-family:'Poppins',sans-serif;font-weight:800;font-size:10px;line-height:1;cursor:pointer;vertical-align:middle;flex-shrink:0;user-select:none}
  .info-i .bubble{position:absolute;bottom:calc(100% + 9px);left:50%;z-index:50;width:max-content;max-width:210px;
    background:#0F1830;color:#fff !important;font-family:'Poppins',sans-serif;font-weight:500;font-size:11.5px;letter-spacing:0;text-transform:none;
    line-height:1.45;padding:9px 11px;border-radius:10px;box-shadow:0 12px 28px rgba(15,24,48,.32);
    opacity:0;visibility:hidden;transform:translate(-50%,4px);transition:opacity .15s, transform .15s, visibility .15s;text-align:center;white-space:normal}
  .info-i .bubble::after{content:'';position:absolute;top:100%;left:50%;transform:translateX(-50%);border:6px solid transparent;border-top-color:#0F1830}
  .info-i.open .bubble{opacity:1;visibility:visible;transform:translate(-50%,0)}
</style>
@endverbatim
@stack('styles')
</head>
<body>
@php $t = $tab ?? 'home'; @endphp
<div class="app">
  <header class="app-top">
    <a class="app-logo" href="{{ route('gtr') }}" aria-label="Gerung Trail Run 2026">
      <picture>
        <source srcset="{{ asset('assets/gtr/logo.webp') }}" type="image/webp">
        <img class="gtr" src="{{ asset('assets/gtr/logo.jpeg') }}" alt="Gerung Trail Run" width="240" height="181">
      </picture>
      <span class="sep"></span>
      <picture>
        <source srcset="{{ asset('assets/runger-logo-white.webp') }}" type="image/webp">
        <img class="rg" src="{{ asset('assets/runger-logo-white.png') }}" alt="Runger — Runners Gerung" width="1000" height="245">
      </picture>
    </a>
    <span class="app-title">@yield('app-title', 'Akun Peserta')</span>
  </header>

  <main class="app-main">
    @if(session('success'))
      <div class="app-ok">{{ session('success') }}</div>
    @endif
    @yield('content')
  </main>

  <nav class="app-tabs">
    <a href="{{ route('gtr.dashboard') }}" @class(['app-tab', 'active' => $t === 'home'])>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l9-8 9 8"/><path d="M5 10v10h5v-6h4v6h5V10"/></svg>
      <span>Home</span>
    </a>
    <a href="{{ route('gtr.account.race') }}" @class(['app-tab', 'active' => $t === 'race'])>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 21V4"/><path d="M5 4h13l-2.5 4L18 12H5"/></svg>
      <span>My Race</span>
    </a>
    <a href="{{ route('gtr.account.transaction') }}" @class(['app-tab', 'active' => $t === 'transaction'])>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h4"/></svg>
      <span>Transaction</span>
    </a>
    <a href="{{ route('gtr.account.profile') }}" @class(['app-tab', 'active' => $t === 'profile'])>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
      <span>Profile</span>
    </a>
  </nav>
</div>
<script>
  (function () {
    var top = document.querySelector('.app-top');
    var onScroll = function () { top.classList.toggle('compact', window.scrollY > 24); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  })();
  document.addEventListener('click', function (e) {
    const icon = e.target.closest('.info-i');
    document.querySelectorAll('.info-i.open').forEach(function (el) { if (el !== icon) el.classList.remove('open'); });
    if (icon) { e.preventDefault(); icon.classList.toggle('open'); }
  });
</script>
<script>
  // PWA: simpan app untuk mode offline saat race day
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    document.addEventListener('submit', function (e) {
      if (e.target.action && /\/runger-app\/keluar/.test(e.target.action) && navigator.serviceWorker.controller) {
        navigator.serviceWorker.controller.postMessage('clear-pages');
      }
    });
  }
</script>
@stack('scripts')
</body>
</html>
