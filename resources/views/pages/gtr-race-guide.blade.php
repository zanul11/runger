@extends('layouts.gtr')

@section('title', 'Race Guide — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@push('styles')
<style>
  .rg-actions{display:flex;flex-wrap:wrap;justify-content:center;align-items:center;gap:10px 14px;margin-top:22px}
  .rg-updated{font-family:'Archivo',sans-serif;font-weight:700;font-size:11.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--text-mute)}
  .rg-slides{display:grid;grid-template-columns:1fr;gap:18px;max-width:1120px;margin:0 auto}
  .rg-slide{position:relative;border-radius:16px;overflow:hidden;background:#141414;border:1px solid var(--line)}
  .rg-slide button.open{display:block;width:100%;padding:0;border:0;background:none;cursor:zoom-in}
  .rg-slide img{display:block;width:100%;height:auto;aspect-ratio:1080/1350;object-fit:cover}
  .rg-slide-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px;border-top:1px solid var(--line)}
  .rg-slide .no{font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.1em;color:var(--text-soft)}
  .rg-slide .dl{display:inline-flex;align-items:center;gap:8px;padding:8px 13px;border-radius:999px;
    border:1px solid rgba(255,255,255,.25);color:#fff;
    font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;letter-spacing:.1em;text-transform:uppercase;transition:background .2s,border-color .2s}
  .rg-slide .dl svg{width:14px;height:14px}
  .rg-slide .dl:hover{background:var(--red);border-color:var(--red)}
  .rg-empty{max-width:560px;margin:0 auto;text-align:center;padding:40px 24px;border-radius:20px;background:var(--card);border:1px solid var(--line)}
  .rg-empty b{display:block;font-family:'Archivo',sans-serif;font-weight:900;font-size:22px;text-transform:uppercase;margin-bottom:8px}
  .rg-empty p{color:var(--text-soft);font-size:14.5px;line-height:1.6}
  .sc-lb .rg-lb-dl{position:absolute;left:50%;bottom:22px;transform:translateX(-50%);display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:999px;
    background:var(--red);color:#fff;font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;letter-spacing:.1em;text-transform:uppercase}
  .sc-lb .rg-lb-dl svg{width:15px;height:15px}
  #rg-lb img{max-height:78vh}
  #rg-lb .sc-lb-dots{bottom:76px}
  @media (min-width:640px){ .rg-slides{grid-template-columns:repeat(2,1fr)} }
  @media (min-width:1000px){ .rg-slides{grid-template-columns:repeat(3,1fr);gap:22px} }
</style>
@endpush

@section('content')
<section class="block" id="race-guide" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Official Race Guide</div>
      <h2>Race Guide</h2>
      <p>Semua yang perlu kamu tahu sebelum race day: pengambilan race pack, race village, rute & titik pos, perlengkapan wajib, sampai aturan lomba. Baca dengan teliti, simpan, dan bagikan ke teman satu tim.</p>
      @if($published && $slides->isNotEmpty())
        <div class="rg-actions">
          @if($hasZip)
            <a class="btn-primary" href="{{ route('gtr.guide.zip') }}">
              Unduh Semua (ZIP)
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
            </a>
          @endif
          @if($updatedAt)
            <span class="rg-updated">Diperbarui {{ $updatedAt->timezone('Asia/Makassar')->translatedFormat('d M Y, H:i') }} WITA · {{ $slides->count() }} halaman</span>
          @endif
        </div>
      @endif
    </div>

    @if($published && $slides->isNotEmpty())
      <div class="rg-slides">
        @foreach($slides as $i => $sl)
          <div class="rg-slide">
            <button type="button" class="open" data-idx="{{ $i }}" aria-label="Lihat halaman {{ $i + 1 }}">
              <img src="{{ $sl['url'] }}" alt="Race Guide halaman {{ $i + 1 }}" loading="{{ $i < 3 ? 'eager' : 'lazy' }}" decoding="async" width="1080" height="1350">
            </button>
            <div class="rg-slide-bar">
            <span class="no">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</span>
            <a class="dl" href="{{ $sl['url'] }}" download="Race Guide GTR - {{ $sl['name'] }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
              Unduh
            </a>
            </div>
          </div>
        @endforeach
      </div>

      <div class="sc-lb" id="rg-lb" aria-hidden="true">
        <div class="sc-lb-count" id="rg-lb-count"></div>
        <button type="button" class="sc-lb-close" id="rg-lb-close" aria-label="Tutup">×</button>
        <button type="button" class="sc-lb-btn sc-lb-prev" id="rg-lb-prev" aria-label="Sebelumnya">‹</button>
        <img id="rg-lb-img" alt="">
        <button type="button" class="sc-lb-btn sc-lb-next" id="rg-lb-next" aria-label="Berikutnya">›</button>
        <div class="sc-lb-dots" id="rg-lb-dots"></div>
        <a class="rg-lb-dl" id="rg-lb-dl" href="#" download>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
          Unduh halaman ini
        </a>
      </div>
    @else
      <div class="rg-empty">
        <b>Segera Hadir</b>
        <p>Race guide resmi Gerung Trail Run sedang disiapkan panitia dan akan tampil di halaman ini menjelang race day. Sementara itu, cek <a href="{{ route('gtr.rules') }}" style="color:#fff;text-decoration:underline">Rules and Regulations</a>.</p>
      </div>
    @endif
  </div>
</section>
@endsection

@if($published && $slides->isNotEmpty())
@push('scripts')
<script>
(function () {
  const slides = @json($slides->values());
  const lb = document.getElementById('rg-lb'), img = document.getElementById('rg-lb-img');
  const count = document.getElementById('rg-lb-count'), dots = document.getElementById('rg-lb-dots'), dl = document.getElementById('rg-lb-dl');
  let idx = 0;
  dots.innerHTML = slides.map((_, i) => '<i data-i="' + i + '"></i>').join('');
  function show(i) {
    idx = (i + slides.length) % slides.length;
    img.src = slides[idx].url;
    dl.href = slides[idx].url;
    dl.setAttribute('download', 'Race Guide GTR - ' + slides[idx].name);
    count.textContent = String(idx + 1).padStart(2, '0') + ' / ' + String(slides.length).padStart(2, '0');
    [...dots.children].forEach((d, k) => d.classList.toggle('on', k === idx));
  }
  function open(i) { show(i); lb.classList.add('open'); lb.setAttribute('aria-hidden', 'false'); document.body.style.overflow = 'hidden'; }
  function close() { lb.classList.remove('open'); lb.setAttribute('aria-hidden', 'true'); document.body.style.overflow = ''; }
  document.querySelectorAll('.rg-slide .open').forEach(b => b.addEventListener('click', () => open(+b.dataset.idx)));
  document.getElementById('rg-lb-close').addEventListener('click', close);
  document.getElementById('rg-lb-prev').addEventListener('click', e => { e.stopPropagation(); show(idx - 1); });
  document.getElementById('rg-lb-next').addEventListener('click', e => { e.stopPropagation(); show(idx + 1); });
  dots.addEventListener('click', e => { if (e.target.dataset.i) show(+e.target.dataset.i); });
  lb.addEventListener('click', e => { if (e.target === lb) close(); });
  document.addEventListener('keydown', e => {
    if (!lb.classList.contains('open')) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowLeft') show(idx - 1);
    if (e.key === 'ArrowRight') show(idx + 1);
  });
  let x0 = null;
  lb.addEventListener('touchstart', e => { x0 = e.touches[0].clientX; }, {passive: true});
  lb.addEventListener('touchend', e => {
    if (x0 === null) return;
    const dx = e.changedTouches[0].clientX - x0; x0 = null;
    if (Math.abs(dx) > 40) show(idx + (dx < 0 ? 1 : -1));
  });
})();
</script>
@endpush
@endif
