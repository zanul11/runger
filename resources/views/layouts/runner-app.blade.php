<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
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
    position:sticky;top:0;z-index:10;display:flex;align-items:center;gap:11px;
    padding:13px 18px;
    background:linear-gradient(110deg,#1B3FAE 0%,#1638A0 52%,#0F2680 100%);
    box-shadow:0 6px 18px rgba(15,38,128,.28);
  }
  .app-top img{height:26px;width:auto}
  .app-top .app-title{font-family:'Poppins',sans-serif;font-weight:800;font-size:14px;letter-spacing:.06em;text-transform:uppercase;color:#fff}
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
    <img src="{{ asset('logo-white.png') }}" alt="Runger">
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
  document.addEventListener('click', function (e) {
    const icon = e.target.closest('.info-i');
    document.querySelectorAll('.info-i.open').forEach(function (el) { if (el !== icon) el.classList.remove('open'); });
    if (icon) { e.preventDefault(); icon.classList.toggle('open'); }
  });
</script>
@stack('scripts')
</body>
</html>
