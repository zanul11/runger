{{--
  Ajakan memasang Runger App (PWA). Param $variant:
    'card'   — selalu tampil selama belum terpasang (Profil), termasuk petunjuk manual.
    'banner' — ringkas & bisa ditutup (Home / Masuk); hanya bila dialog pasang siap atau di iPhone/iPad.
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
  .pwa-b ol{margin:6px 0 0 18px;font-size:12.5px;color:#5B6378;line-height:1.6}
  .pwa-b ol svg{width:15px;height:15px;vertical-align:-3px}
  .pwa-go{display:inline-flex;align-items:center;gap:7px;margin-top:10px;padding:10px 16px;border-radius:999px;border:none;cursor:pointer;
    background:#E53935;color:#fff;font-family:'Poppins',sans-serif;font-weight:800;font-size:12px;letter-spacing:.03em;text-transform:uppercase;
    box-shadow:0 8px 18px rgba(229,57,53,.3)}
  .pwa-go svg{width:16px;height:16px}
  .pwa-x{position:absolute;top:8px;right:8px;width:30px;height:30px;border-radius:50%;border:none;background:none;color:#8A92A6;font-size:18px;cursor:pointer}
  .pwa.banner .pwa-b{padding-right:22px}

  /* visibilitas per mode (class di <html> dari pwa-head) */
  .pwa [data-m]{display:none}
  html.pwa-can .pwa [data-m="prompt"]{display:block}
  html.pwa-ios:not(.pwa-can) .pwa [data-m="ios"]{display:block}
  html:not(.pwa-can):not(.pwa-ios) .pwa [data-m="manual"]{display:block}
  html.pwa-installed .pwa{display:none}
  .pwa.banner{display:none}
  html.pwa-can:not(.pwa-dismissed):not(.pwa-installed) .pwa.banner,
  html.pwa-ios:not(.pwa-dismissed):not(.pwa-installed) .pwa.banner{display:flex}
</style>
@endverbatim
@endpush
@push('scripts')
<script>
  (function () {
    document.addEventListener('click', async function (e) {
      if (e.target.closest('[data-pwa-install]')) {
        var ev = window.__pwaPrompt;
        if (!ev) return;
        window.__pwaPrompt = null;
        document.documentElement.classList.remove('pwa-can'); // satu event hanya bisa dipakai sekali
        ev.prompt();
        try { await ev.userChoice; } catch (err) {}
      } else if (e.target.closest('[data-pwa-dismiss]')) {
        document.documentElement.classList.add('pwa-dismissed');
        try { localStorage.setItem('runger-install-dismissed', '1'); } catch (err) {}
      }
    });
  })();
</script>
@endpush
@endonce

<section class="pwa {{ $variant === 'banner' ? 'banner' : '' }}" aria-label="Pasang Runger App">
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
  @if($variant === 'banner')
    <button type="button" class="pwa-x" data-pwa-dismiss aria-label="Tutup ajakan pasang">×</button>
  @endif
</section>
