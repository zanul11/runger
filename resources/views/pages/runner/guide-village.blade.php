@extends('layouts.runner-app')
@section('title', 'Peta Race Village — Runger')
@section('app-title', 'Race Village')

@push('styles')
@verbatim
<style>
  .back{display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;font-weight:700;font-size:12.5px;color:var(--blue)}
  .rv-acts{display:flex;gap:8px;margin-bottom:14px}
  .rv-acts button{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 12px;border-radius:12px;cursor:pointer;
    font-family:'Poppins',sans-serif;font-weight:800;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;border:1px solid #0E2217;color:#fff;background:#0E2217}
  .rv-acts svg{width:15px;height:15px}

  /* Peta: iframe di dalam halaman; mode layar penuh = overlay tetap dengan bar tutup di atas. */
  .rv-wrap{position:relative;display:flex;flex-direction:column;border-radius:18px;overflow:hidden;background:#0E2217;
    border:1px solid var(--line);box-shadow:0 10px 26px rgba(14,34,23,.22);height:max(440px, calc(100dvh - 330px))}
  .rv-bar{display:none}
  .rv-wrap iframe{flex:1;display:block;width:100%;min-height:0;border:0}
  .rv-ld{position:absolute;inset:0;display:grid;place-items:center;pointer-events:none;color:rgba(244,246,239,.6);
    font-weight:800;font-size:11px;letter-spacing:.14em;text-transform:uppercase}
  .rv-wrap.ready .rv-ld{display:none}

  .rv-wrap.full{position:fixed;inset:0;z-index:80;height:auto;border-radius:0;border:0}
  .rv-wrap.full .rv-bar{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;
    padding:calc(8px + env(safe-area-inset-top)) 14px 8px;background:#0E2217;color:#F4F6EF;border-bottom:1px solid rgba(217,242,122,.25)}
  .rv-bar b{font-weight:800;font-size:12px;letter-spacing:.12em;text-transform:uppercase}
  .rv-bar button{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;border:0;cursor:pointer;
    background:#D9F27A;color:#0E2217;font-family:'Poppins',sans-serif;font-weight:800;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase}
  .rv-bar svg{width:14px;height:14px}
  body.rv-lock{overflow:hidden}
  body.rv-lock .app-tabs{display:none}

  .rv-note{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:12px;margin-top:14px;
    background:#EEF2FF;border:1px solid #D6DEFA;color:#2B3A75;font-size:12px;line-height:1.5}
  .rv-note svg{width:16px;height:16px;flex-shrink:0;margin-top:1px;color:var(--blue)}
</style>
@endverbatim
@endpush

@section('content')
<a class="back" href="{{ route('gtr.account.guide') }}">← Race Guide &amp; Dokumen</a>
<div class="sec-title">Peta Race Village</div>
<div class="sec-sub">Lihat posisi stage, tenda medali, refreshment, toilet &amp; gate start sebelum datang ke venue.</div>

<div class="rv-acts">
  <button type="button" data-rv-full>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5"/></svg>
    Layar penuh
  </button>
</div>

<div class="rv-wrap" id="rv-wrap">
  <div class="rv-bar">
    <b>Race Village · GTR 2026</b>
    <button type="button" data-rv-close>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
      Tutup
    </button>
  </div>
  <iframe src="{{ $village }}" title="Peta 3D Race Village Gerung Trail Run 2026" allowfullscreen></iframe>
  <span class="rv-ld">Memuat peta 3D…</span>
</div>

<div class="rv-note">
  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
  <span>Seret untuk memutar, cubit untuk zoom. Ketuk tombol <b>Cari Fasilitas</b> di peta untuk langsung menuju lokasinya. Buka sekali saat ada sinyal agar peta tersimpan di HP.</span>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var wrap = document.getElementById('rv-wrap');
  var frame = wrap.querySelector('iframe');
  frame.addEventListener('load', function () { wrap.classList.add('ready'); });
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
