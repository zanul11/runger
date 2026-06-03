@props(['active' => 'home'])
<style>
  nav.site-top{position:sticky;top:0;z-index:50;background:var(--runger-blue);color:var(--bone);border-bottom:1px solid rgba(255,255,255,.12)}
  .site-nav-inner{max-width:1320px;margin:0 auto;padding:12px 16px;display:flex;align-items:center;gap:12px;justify-content:space-between}
  .site-nav-logo img{height:30px}
  .site-nav-links{display:none}
  .site-nav-cta{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;background:var(--volt);color:var(--ink);font-size:11.5px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;transition:transform .15s, background .2s}
  .site-nav-cta:hover{transform:translateY(-1px)}
  .site-burger{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.25);border-radius:50%;color:#fff}
  .site-burger span{display:block;width:14px;height:1.5px;background:#fff;position:relative}
  .site-burger span::before,.site-burger span::after{content:'';position:absolute;left:0;width:14px;height:1.5px;background:#fff}
  .site-burger span::before{top:-4px}
  .site-burger span::after{top:4px}
  .site-drawer{position:fixed;inset:0;z-index:90;background:rgba(10,15,44,.96);backdrop-filter:blur(12px);display:none;flex-direction:column;padding:80px 24px 24px}
  .site-drawer.open{display:flex}
  .site-drawer-close{position:absolute;top:14px;right:16px;width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(255,255,255,.18);border-radius:50%;color:#fff;font-size:20px}
  .site-drawer a{padding:16px 0;border-bottom:1px solid rgba(255,255,255,.1);font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:.04em;text-transform:uppercase;color:#fff}
  .site-drawer a.active{color:var(--volt)}
  @media (min-width:820px){
    .site-nav-inner{padding:14px 24px;gap:24px}
    .site-nav-logo img{height:34px}
    .site-burger{display:none}
    .site-nav-links{display:flex;gap:24px;font-size:13px;font-weight:500;letter-spacing:.02em}
    .site-nav-links a{opacity:.85;transition:opacity .15s;padding:4px 0;position:relative}
    .site-nav-links a:hover{opacity:1}
    .site-nav-links a.active{opacity:1}
    .site-nav-links a.active::after{content:'';position:absolute;left:0;right:0;bottom:-2px;height:2px;background:var(--volt)}
  }
</style>
<nav class="site-top">
  <div class="site-nav-inner">
    <a class="site-nav-logo" href="{{ route('home') }}">
      <img src="{{ asset('assets/runger-logo.png') }}" alt="Runger">
    </a>
    <div class="site-nav-links">
      <a href="{{ route('home') }}#night" @class(['active' => $active === 'home'])>Night Run</a>
      <a href="{{ route('home') }}#tentang">Tentang</a>
      <a href="{{ route('home') }}#jadwal">Jadwal</a>
      <a href="{{ route('agenda') }}" @class(['active' => $active === 'agenda'])>Agenda</a>
      <a href="{{ route('gallery') }}" @class(['active' => $active === 'gallery'])>Galeri</a>
    </div>
    <a class="site-nav-cta" href="{{ \App\Models\Setting::get('social.instagram_url', '#') }}" target="_blank" rel="noopener">Gabung →</a>
    <button class="site-burger" id="site-burger" aria-label="Open menu" type="button"><span></span></button>
  </div>
</nav>
<div class="site-drawer" id="site-drawer" aria-hidden="true">
  <button class="site-drawer-close" id="site-drawer-close" aria-label="Close menu" type="button">×</button>
  <a href="{{ route('home') }}" @class(['active' => $active === 'home'])>Home</a>
  <a href="{{ route('home') }}#night">Night Run</a>
  <a href="{{ route('home') }}#tentang">Tentang</a>
  <a href="{{ route('home') }}#jadwal">Jadwal</a>
  <a href="{{ route('agenda') }}" @class(['active' => $active === 'agenda'])>Agenda</a>
  <a href="{{ route('gallery') }}" @class(['active' => $active === 'gallery'])>Galeri</a>
</div>
<script>
  (function(){
    const b = document.getElementById('site-burger');
    const d = document.getElementById('site-drawer');
    const c = document.getElementById('site-drawer-close');
    b?.addEventListener('click', () => d.classList.add('open'));
    c?.addEventListener('click', () => d.classList.remove('open'));
    d?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => d.classList.remove('open')));
  })();
</script>
