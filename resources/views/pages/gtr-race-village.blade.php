@extends('layouts.gtr')

@section('title', 'Race Village — Gerung Trail Run 2026')
@section('description', 'Peta 3D race village Gerung Trail Run 2026 di SD Negeri 4 Banyu Urip: stage, tenda medali, refreshment, toilet, photo booth & gate start.')
@section('bodyClass', 'gtr-sub')

@push('styles')
<style>
  .rv-actions{display:flex;flex-wrap:wrap;justify-content:center;gap:10px 12px;margin-top:22px}
  .rv-actions button{cursor:pointer}
  .rv-mode{display:inline-flex;gap:4px;margin-top:22px;padding:4px;border-radius:999px;background:var(--bg-soft);border:1px solid var(--line)}
  .rv-mode button{padding:10px 18px;border:0;border-radius:999px;cursor:pointer;background:transparent;color:var(--text-soft);
    font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.1em;text-transform:uppercase;transition:background .2s,color .2s}
  .rv-mode button[aria-selected="true"]{background:#D9F27A;color:#0E2217}
  .rv-mode + .rv-actions{margin-top:14px}

  /* Peta 3D (iframe). Layar penuh = overlay tetap dengan bar Tutup di atas (jalan juga di iPhone). */
  .rv-wrap{position:relative;display:flex;flex-direction:column;max-width:1120px;margin:0 auto;border-radius:20px;overflow:hidden;
    background:#0E2217;border:1px solid var(--line);height:max(480px, calc(100svh - 220px))}
  .rv-wrap iframe{flex:1;display:block;width:100%;min-height:0;border:0}
  .rv-ld{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none;color:rgba(244,246,239,.6);
    font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.18em;text-transform:uppercase}
  .rv-wrap.ready .rv-ld{display:none}
  .rv-bar{display:none}
  .rv-wrap.full{position:fixed;inset:0;z-index:1000;max-width:none;height:auto;border-radius:0;border:0}
  .rv-wrap.full .rv-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;
    padding:calc(8px + env(safe-area-inset-top)) 16px 8px;background:#0E2217;color:#F4F6EF;border-bottom:1px solid rgba(217,242,122,.25)}
  .rv-bar b{font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.14em;text-transform:uppercase}
  .rv-bar button{display:inline-flex;align-items:center;gap:6px;padding:9px 15px;border-radius:999px;border:0;cursor:pointer;
    background:#D9F27A;color:#0E2217;font-family:'Archivo',sans-serif;font-weight:800;font-size:11.5px;letter-spacing:.1em;text-transform:uppercase}
  .rv-bar svg{width:14px;height:14px}
  body.rv-lock{overflow:hidden}

  .rv-tip{max-width:1120px;margin:14px auto 0;text-align:center;color:var(--text-mute);font-size:13px;line-height:1.6}
  @media (max-width:819px){ .rv-wrap{border-radius:16px;height:max(440px, calc(100svh - 200px))} }
</style>
@endpush

@section('content')
<section class="block" id="race-village" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Race Info · Peta Venue</div>
      <h2>Race Village</h2>
      <p>Kenali venue sebelum race day: stage, tenda medali, refreshment, toilet, photo booth sampai gate start. Pilih <b>Cari Fasilitas</b> di peta untuk langsung menuju lokasinya.</p>
      @if($village2d)
        <div class="rv-mode" role="tablist" aria-label="Tampilan peta">
          <button type="button" role="tab" aria-selected="true" data-rv-mode="{{ $village }}">Peta 3D</button>
          <button type="button" role="tab" aria-selected="false" data-rv-mode="{{ $village2d }}">Denah 2D</button>
        </div>
      @endif
      <div class="rv-actions">
        <button type="button" class="btn-primary" data-rv-full>
          Layar penuh
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
        </button>
        <a class="btn-ghost" href="{{ $village }}" target="_blank" rel="noopener" data-rv-open>Buka di tab baru ↗</a>
      </div>
    </div>

    <div class="rv-wrap" id="rv-wrap">
      <div class="rv-bar">
        <b>Race Village · GTR 2026</b>
        <button type="button" data-rv-close>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          Tutup
        </button>
      </div>
      <iframe src="{{ $village }}" title="Peta 3D Race Village Gerung Trail Run 2026" loading="lazy" allowfullscreen></iframe>
      <span class="rv-ld">Memuat peta 3D…</span>
    </div>
    <p class="rv-tip">Seret untuk memutar · cubit / scroll untuk zoom</p>
  </div>
</section>
@endsection

@push('scripts')
<script>
(function () {
  var wrap = document.getElementById('rv-wrap');
  var frame = wrap.querySelector('iframe');
  frame.addEventListener('load', function () { wrap.classList.add('ready'); });
  // 3D ↔ Denah 2D: ganti isi iframe (+ link "Buka di tab baru").
  document.querySelectorAll('[data-rv-mode]').forEach(function (b) {
    b.addEventListener('click', function () {
      if (b.getAttribute('aria-selected') === 'true') return;
      document.querySelectorAll('[data-rv-mode]').forEach(function (x) { x.setAttribute('aria-selected', String(x === b)); });
      wrap.classList.remove('ready');
      frame.src = b.dataset.rvMode;
      document.querySelector('[data-rv-open]').href = b.dataset.rvMode;
    });
  });
  var setFull = function (on) {
    wrap.classList.toggle('full', on);
    document.body.classList.toggle('rv-lock', on);
  };
  document.querySelector('[data-rv-full]').addEventListener('click', function () { setFull(true); });
  wrap.querySelector('[data-rv-close]').addEventListener('click', function () { setFull(false); });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setFull(false); });
})();
</script>
@endpush
