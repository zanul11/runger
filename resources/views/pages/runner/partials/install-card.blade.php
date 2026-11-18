{{--
  Ajakan memasang Runger App (PWA). Param $variant:
    'card'  — kartu di halaman (Profil): selalu tampil selama belum terpasang, termasuk petunjuk manual.
    'popup' — pop-up bawah (Home / Masuk) yang muncul otomatis bila dialog pasang browser siap atau di
              iPhone/iPad. "Nanti saja" menunda pop-up 3 hari. Dialog asli browser hanya boleh dipanggil
              dari ketukan pengguna, jadi pop-up ini yang muncul dulu, lalu tombol "Pasang sekarang".
  Mode dipilih lewat class di <html> dari partial pwa-head: pwa-can → tombol Pasang, pwa-ios → langkah
  Bagikan, selain itu → menu browser.
--}}
@php $variant = $variant ?? 'card'; @endphp

@once
@push('styles')
@verbatim
<style>
  .pwa{position:relative;display:flex;gap:13px;align-items:flex-start;padding:15px 16px;border-radius:18px;background:#fff;border:1px solid var(--line,#E6EAF3);
    box-shadow:0 6px 18px rgba(15,24,48,.06);color:var(--text,#0F1830);margin:12px 0;text-align:left}
  .pwa-ic{width:48px;height:48px;flex-shrink:0;border-radius:12px;border:1px solid #E6EAF3;background:#fff;object-fit:contain}
  .pwa-b{flex:1;min-width:0}
  .pwa-b b{display:block;font-weight:800;font-size:14px;line-height:1.3}
  .pwa-b p{font-size:12.5px;color:#5B6378;line-height:1.5;margin-top:3px}
  .pwa ol{margin:6px 0 0 18px;font-size:12.5px;color:#5B6378;line-height:1.6;text-align:left}
  .pwa ol svg,.pwa-pop ol svg{width:15px;height:15px;vertical-align:-3px}
  .pwa-go{display:inline-flex;align-items:center;justify-content:center;gap:7px;margin-top:10px;padding:10px 16px;border-radius:999px;border:none;cursor:pointer;
    background:#E53935;color:#fff;font-family:'Poppins',sans-serif;font-weight:800;font-size:12px;letter-spacing:.03em;text-transform:uppercase;
    box-shadow:0 8px 18px rgba(229,57,53,.3)}
  .pwa-go svg{width:16px;height:16px}

  /* pop-up bawah */
  .pwa-pop{position:fixed;inset:0;z-index:70;display:flex;align-items:flex-end;justify-content:center;font-family:'Poppins',sans-serif}
  .pwa-pop[hidden]{display:none}
  .pwa-pop-bg{position:absolute;inset:0;background:rgba(8,10,18,.55);backdrop-filter:blur(3px);animation:pwa-fade .2s ease}
  .pwa-pop-card{position:relative;width:100%;max-width:480px;background:#fff;color:#0F1830;border-radius:26px 26px 0 0;text-align:center;
    padding:10px 22px calc(20px + env(safe-area-inset-bottom));animation:pwa-up .28s ease;box-shadow:0 -12px 40px rgba(0,0,0,.2)}
  .pwa-pop-grab{display:block;width:44px;height:5px;border-radius:5px;background:#E1E5EE;margin:0 auto 16px}
  .pwa-pop-ic{display:block;width:78px;height:78px;margin:0 auto;border-radius:20px;border:1px solid #E6EAF3;background:#fff;object-fit:contain;box-shadow:0 10px 24px rgba(15,24,48,.12)}
  .pwa-pop h3{font-weight:900;font-size:20px;margin-top:14px}
  .pwa-pop .lead{font-size:13px;color:#5B6378;line-height:1.5;margin:6px auto 0;max-width:34ch}
  .pwa-pop ul{list-style:none;display:flex;flex-direction:column;gap:7px;margin:16px 0 4px;padding:0;text-align:left}
  .pwa-pop ul li{display:flex;gap:10px;align-items:center;font-size:13px;font-weight:600;padding:10px 12px;border-radius:13px;background:#F4F6FB}
  .pwa-pop ul li span{flex-shrink:0;width:26px;height:26px;border-radius:8px;display:grid;place-items:center;background:#fff;font-size:14px}
  .pwa-pop ol{text-align:left;line-height:1.8;margin:12px 0 0;padding:12px 14px 12px 32px;border-radius:13px;background:#F4F6FB;font-size:13px;color:#0F1830}
  .pwa-pop .pwa-go{width:100%;margin-top:16px;padding:15px;border-radius:14px;font-size:13.5px}
  .pwa-later{width:100%;margin-top:8px;padding:12px;border:none;background:none;cursor:pointer;font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;color:#8A92A6}
  @keyframes pwa-up{from{transform:translateY(60px);opacity:.3}}
  @keyframes pwa-fade{from{opacity:0}}
  @media (prefers-reduced-motion:reduce){.pwa-pop-bg,.pwa-pop-card{animation:none}}

  /* visibilitas per mode (class di <html> dari pwa-head) */
  [data-m]{display:none}
  html.pwa-can [data-m="prompt"]{display:block}
  html.pwa-ios:not(.pwa-can) [data-m="ios"]{display:block}
  html:not(.pwa-can):not(.pwa-ios) [data-m="manual"]{display:block}
  html.pwa-installed .pwa,html.pwa-installed .pwa-pop{display:none}
</style>
@endverbatim
@endpush
@push('scripts')
<script>
  (function () {
    var cl = document.documentElement.classList;
    var pop = document.getElementById('pwa-pop');
    var snooze = function () {
      cl.add('pwa-dismissed');
      try { localStorage.setItem('runger-install-snooze', String(Date.now() + 3 * 864e5)); } catch (err) {}
    };
    var close = function () { if (pop) pop.hidden = true; document.body.style.overflow = ''; };

    // Pop-up otomatis: hanya bila bisa dipasang (dialog siap / iOS), belum terpasang, dan tidak sedang ditunda.
    if (pop) {
      // Pindah ke <body>: panel login punya transform/z-index sendiri yang bisa mengurung position:fixed.
      document.body.appendChild(pop);
      var shown = false;
      var eligible = function () {
        return !cl.contains('pwa-installed') && !cl.contains('pwa-dismissed') && (cl.contains('pwa-can') || cl.contains('pwa-ios'));
      };
      var open = function () {
        if (shown || !eligible() || document.querySelector('#qr-sheet:not([hidden])')) return;
        shown = true;
        pop.hidden = false;
        document.body.style.overflow = 'hidden';
      };
      setTimeout(open, 1500);
      // Chrome mengirim beforeinstallprompt beberapa saat setelah halaman dimuat.
      window.addEventListener('pwa:change', function () {
        if (cl.contains('pwa-installed')) close(); else setTimeout(open, 800);
      });
    }

    document.addEventListener('click', async function (e) {
      if (e.target.closest('[data-pwa-install]')) {
        var ev = window.__pwaPrompt;
        if (!ev) return;
        window.__pwaPrompt = null;
        cl.remove('pwa-can'); // satu event hanya bisa dipakai sekali
        close();
        ev.prompt();
        try { if ((await ev.userChoice).outcome !== 'accepted') snooze(); } catch (err) {}
      } else if (e.target.closest('[data-pwa-later]')) {
        snooze();
        close();
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && pop && !pop.hidden) { snooze(); close(); }
    });
  })();
</script>
@endpush
@endonce

@if($variant === 'popup')
  <div class="pwa-pop" id="pwa-pop" hidden>
    <div class="pwa-pop-bg" data-pwa-later></div>
    <div class="pwa-pop-card" role="dialog" aria-modal="true" aria-labelledby="pwa-pop-t">
      <span class="pwa-pop-grab"></span>
      <img class="pwa-pop-ic" src="/pwa/runger-app-192.png" alt="" width="78" height="78">
      <h3 id="pwa-pop-t">Pasang Runger App</h3>
      <p class="lead">Simpan aplikasi peserta Gerung Trail Run di layar utama HP kamu.</p>
      <ul>
        <li><span>🎟️</span>E-ticket QR tetap bisa dibuka tanpa sinyal</li>
        <li><span>⚡</span>Buka langsung dari layar utama, layar penuh</li>
        <li><span>🗺️</span>Rute, race plan &amp; jadwal selalu di tangan</li>
      </ul>
      <div data-m="prompt">
        <button type="button" class="pwa-go" data-pwa-install><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Pasang sekarang</button>
      </div>
      <div data-m="ios">
        <ol>
          <li>Ketuk <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg> <b>Bagikan</b> di bilah Safari</li>
          <li>Pilih <b>Tambah ke Layar Utama</b></li>
          <li>Ketuk <b>Tambah</b></li>
        </ol>
      </div>
      <button type="button" class="pwa-later" data-pwa-later>Nanti saja</button>
    </div>
  </div>
@else
  <section class="pwa" aria-label="Pasang Runger App">
    <img class="pwa-ic" src="/pwa/runger-app-192.png" alt="" width="48" height="48">
    <div class="pwa-b">
      <b>Pasang Runger App di HP</b>
      <div data-m="prompt">
        <p>Buka langsung dari layar utama, layar penuh, dan e-ticket tetap bisa dibuka tanpa sinyal.</p>
        <button type="button" class="pwa-go" data-pwa-install><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>Pasang aplikasi</button>
      </div>
      <div data-m="ios">
        <p>Di iPhone/iPad, pasang lewat Safari:</p>
        <ol>
          <li>Ketuk <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg> <b>Bagikan</b></li>
          <li>Pilih <b>Tambah ke Layar Utama</b></li>
          <li>Ketuk <b>Tambah</b></li>
        </ol>
      </div>
      <div data-m="manual">
        <p>Pasang lewat menu browser (⋮), lalu pilih <b>Pasang aplikasi</b> / <b>Tambahkan ke layar utama</b>.</p>
      </div>
    </div>
  </section>
@endif
