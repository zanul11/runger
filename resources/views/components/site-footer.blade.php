<style>
  footer.site-foot{background:#000;color:var(--bone);padding:24px 16px;border-top:1px solid rgba(255,255,255,.08);font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;opacity:.6}
  .site-foot-inner{max-width:1320px;margin:0 auto;display:flex;justify-content:center;flex-wrap:wrap;gap:10px;text-align:center}
  @media (min-width:820px){
    footer.site-foot{padding:24px 32px}
    .site-foot-inner{justify-content:space-between;text-align:left}
  }
</style>
<footer class="site-foot">
  <div class="site-foot-inner">
    <span>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('site.name', 'Runger') }}</span>
    <span>{{ \App\Models\Setting::get('site.location', 'Gerung, Lombok Barat') }}</span>
    <span>Made with sweat 🏃</span>
  </div>
</footer>
