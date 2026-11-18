{{--
  PWA Runger App (di <head> layout app & halaman Masuk/Buat Akun): manifest, ikon logo resmi GTR, dan
  penangkap event pasang. Event `beforeinstallprompt` harus ditangkap sedini mungkin supaya tombol
  "Pasang" bisa memanggil dialog browser kapan saja. Status ditulis sebagai class di <html>:
    pwa-installed (dibuka sebagai app) · pwa-can (dialog pasang siap) · pwa-ios · pwa-dismissed (pop-up ditunda "Nanti saja").
--}}
<link rel="manifest" href="/manifest.webmanifest">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/runger-app-192.png">
<link rel="apple-touch-icon" href="/pwa/runger-app-apple-touch.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Runger App">
<meta name="application-name" content="Runger App">
<script>
  (function () {
    var w = window, cl = document.documentElement.classList, ua = navigator.userAgent;
    var emit = function () { w.dispatchEvent(new Event('pwa:change')); };
    w.__pwaPrompt = null;
    if ((w.matchMedia && w.matchMedia('(display-mode: standalone)').matches) || w.navigator.standalone === true) cl.add('pwa-installed');
    // iPadOS 13+ mengaku "Macintosh" tapi punya layar sentuh.
    if (/iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1)) cl.add('pwa-ios');
    try { if (Number(localStorage.getItem('runger-install-snooze')) > Date.now()) cl.add('pwa-dismissed'); } catch (e) {}
    w.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); w.__pwaPrompt = e; cl.add('pwa-can'); emit(); });
    w.addEventListener('appinstalled', function () { w.__pwaPrompt = null; cl.remove('pwa-can'); cl.add('pwa-installed'); emit(); });
  })();
</script>
