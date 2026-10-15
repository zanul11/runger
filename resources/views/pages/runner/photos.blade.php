@extends('layouts.runner-app')
@section('title', 'My Photo — Runger')
@section('app-title', 'My Photo')

@push('styles')
@verbatim
<style>
  .ph-back{display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;font-weight:700;font-size:12px;color:var(--soft)}
  .ph-head{margin-bottom:14px}
  .ph-head b{display:block;font-weight:900;font-size:20px;color:var(--text)}
  .ph-head small{font-size:12.5px;color:var(--soft)}
  .ph-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:8px}
  .ph-grid button{position:relative;padding:0;border:none;border-radius:14px;overflow:hidden;aspect-ratio:3/4;background:#ddd;cursor:pointer}
  .ph-grid img{width:100%;height:100%;object-fit:cover;display:block}
  .ph-grid span{position:absolute;left:6px;bottom:6px;padding:3px 8px;border-radius:999px;background:rgba(0,0,0,.55);color:#fff;font-size:10px;font-weight:700}
  .ph-empty{padding:30px 18px;text-align:center;background:#fff;border:1px solid var(--line);border-radius:18px;color:var(--soft);font-size:13.5px;line-height:1.5}
  .lb{position:fixed;inset:0;z-index:100;background:rgba(0,0,0,.94);display:none;flex-direction:column}
  .lb.on{display:flex}
  .lb-top{display:flex;justify-content:space-between;align-items:center;padding:calc(12px + env(safe-area-inset-top)) 14px 12px;color:#fff;font-size:13px;font-weight:700}
  .lb-top button,.lb-top a{padding:9px 14px;border-radius:999px;border:none;background:rgba(255,255,255,.14);color:#fff;font-weight:800;font-size:12px}
  .lb-img{flex:1;display:flex;align-items:center;justify-content:center;padding:0 8px;touch-action:pan-y}
  .lb-img img{max-width:100%;max-height:100%;object-fit:contain}
  .lb-nav{display:flex;justify-content:space-between;padding:12px 14px calc(16px + env(safe-area-inset-bottom))}
  .lb-nav button{width:48px;height:48px;border-radius:50%;border:none;background:rgba(255,255,255,.14);color:#fff;font-size:22px}
</style>
@endverbatim
@endpush

@section('content')
<a class="ph-back" href="{{ route('gtr.account.race.detail', $reg) }}">← Detail Race</a>
<div class="ph-head">
  <b>Foto kamu di GTR 2026</b>
  <small>BIB {{ $reg->bib_number ?: '-' }} · {{ $photos->count() }} foto · ketuk untuk memperbesar & download</small>
</div>

@if($photos->count())
  <div class="ph-grid">
    @foreach($photos as $i => $p)
      <button type="button" data-i="{{ $i }}"><img src="{{ $p->url }}" alt="Foto {{ $i + 1 }}" loading="lazy">@if($p->spot)<span>{{ $p->spot }}</span>@endif</button>
    @endforeach
  </div>
@else
  <div class="ph-empty">Belum ada foto yang ditandai dengan BIB kamu. Panitia sedang memproses foto — cek lagi nanti ya!</div>
@endif

<div class="lb" id="lb">
  <div class="lb-top"><span data-lb-count></span><span style="display:flex;gap:8px"><a data-lb-dl download>Download</a><button type="button" data-lb-close>Tutup</button></span></div>
  <div class="lb-img"><img data-lb-img alt=""></div>
  <div class="lb-nav"><button type="button" data-lb-prev>‹</button><button type="button" data-lb-next>›</button></div>
</div>
@endsection

@push('scripts')
<script>
  (function () {
    var urls = @json($photos->pluck('url')->values());
    var lb = document.getElementById('lb'), img = lb.querySelector('[data-lb-img]'), dl = lb.querySelector('[data-lb-dl]'), cnt = lb.querySelector('[data-lb-count]'), i = 0;
    function show(n) { i = (n + urls.length) % urls.length; img.src = urls[i]; dl.href = urls[i]; dl.setAttribute('download', 'gtr2026-foto-' + (i + 1) + '.webp'); cnt.textContent = (i + 1) + ' / ' + urls.length; }
    document.querySelectorAll('[data-i]').forEach(function (b) { b.addEventListener('click', function () { show(Number(b.dataset.i)); lb.classList.add('on'); }); });
    lb.querySelector('[data-lb-close]').addEventListener('click', function () { lb.classList.remove('on'); });
    lb.querySelector('[data-lb-prev]').addEventListener('click', function () { show(i - 1); });
    lb.querySelector('[data-lb-next]').addEventListener('click', function () { show(i + 1); });
    var sx = null;
    lb.addEventListener('touchstart', function (e) { sx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener('touchend', function (e) { if (sx === null) return; var dx = e.changedTouches[0].clientX - sx; if (Math.abs(dx) > 50) show(i + (dx < 0 ? 1 : -1)); sx = null; });
  })();
</script>
@endpush
