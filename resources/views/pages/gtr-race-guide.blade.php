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
  /* Viewer PDF */
  .rg-viewer{max-width:720px;margin:0 auto}
  /* Bar halaman melayang di bawah layar selama PDF terlihat (sticky tidak bisa karena body overflow-x:hidden). */
  .rg-bar{position:fixed;left:50%;bottom:18px;z-index:40;width:min(720px,calc(100% - 32px));transform:translate(-50%,140%);opacity:0;transition:transform .3s ease,opacity .3s ease;
    display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 12px 10px 18px;border-radius:999px;box-shadow:0 12px 34px rgba(0,0,0,.5);
    background:rgba(17,17,17,.88);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:1px solid var(--line)}
  .rg-bar.show{transform:translate(-50%,0);opacity:1}
  .rg-bar .pg{font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;letter-spacing:.12em;color:var(--text-soft)}
  .rg-bar .pg b{color:#fff}
  .rg-bar .acts{display:flex;gap:8px}
  .rg-bar a{display:inline-flex;align-items:center;gap:7px;padding:9px 14px;border-radius:999px;border:1px solid rgba(255,255,255,.22);color:#fff;
    font-family:'Archivo',sans-serif;font-weight:700;font-size:11.5px;letter-spacing:.1em;text-transform:uppercase;transition:background .2s,border-color .2s}
  .rg-bar a svg{width:14px;height:14px}
  .rg-bar a.main{background:var(--red);border-color:var(--red)}
  .rg-bar a:hover{background:var(--red);border-color:var(--red)}
  .rg-pages{display:flex;flex-direction:column;gap:16px}
  .rg-page{position:relative;border-radius:14px;overflow:hidden;background:#141414;border:1px solid var(--line);aspect-ratio:var(--ar,1080/1350)}
  .rg-page canvas{display:block;width:100%;height:100%}
  .rg-page .ld{position:absolute;inset:0;display:grid;place-items:center;font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--text-mute)}
  .rg-page.done .ld{display:none}
  .rg-fallback[hidden]{display:none}
  @media (max-width:520px){ .rg-bar a span{display:none} .rg-bar a{padding:9px 11px} }
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
          @if($pdfUrl)
            <a class="btn-primary" href="{{ route('gtr.guide.pdf') }}">
              Unduh PDF
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
            </a>
          @elseif($hasZip)
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
      @if($pdfUrl)
        <div class="rg-viewer" id="rg-viewer" data-src="{{ $pdfUrl }}">
          <div class="rg-bar">
            <span class="pg">Halaman <b id="rg-pg">01</b> / <span id="rg-pt">{{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</span></span>
            <div class="acts">
              <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" title="Buka PDF di tab baru">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
                <span>Buka</span>
              </a>
              <a class="main" href="{{ route('gtr.guide.pdf') }}" title="Unduh PDF">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
                <span>Unduh PDF</span>
              </a>
            </div>
          </div>
          <div class="rg-pages" id="rg-pages">
            @foreach($slides as $i => $sl)
              <div class="rg-page"><span class="ld">Memuat halaman {{ $i + 1 }}…</span></div>
            @endforeach
          </div>
        </div>
      @endif

      {{-- Cadangan bila PDF tidak tersedia / PDF.js gagal dimuat: tampilkan slide sebagai gambar. --}}
      <div class="rg-fallback" id="rg-fallback" @if($pdfUrl) hidden @endif>
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
@if($pdfUrl)
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
  const viewer = document.getElementById('rg-viewer');
  const fallback = document.getElementById('rg-fallback');
  const useImages = () => { viewer.remove(); fallback.hidden = false; };
  if (!window.pdfjsLib) { useImages(); return; }
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

  const wrap = document.getElementById('rg-pages');
  const pgNow = document.getElementById('rg-pg'), pgTot = document.getElementById('rg-pt');
  const pad = n => String(n).padStart(2, '0');

  pdfjsLib.getDocument(viewer.dataset.src).promise.then(async pdf => {
    // Siapkan wadah sesuai jumlah & rasio halaman PDF.
    const first = (await pdf.getPage(1)).getViewport({scale: 1});
    wrap.innerHTML = '';
    pgTot.textContent = pad(pdf.numPages);
    const boxes = [];
    for (let n = 1; n <= pdf.numPages; n++) {
      const box = document.createElement('div');
      box.className = 'rg-page';
      box.style.setProperty('--ar', first.width + '/' + first.height);
      box.dataset.n = n;
      box.innerHTML = '<span class="ld">Memuat halaman ' + n + '…</span>';
      wrap.appendChild(box);
      boxes.push(box);
    }

    // Halaman dirender saat mendekati layar, tajam sesuai lebar tampilan & kepadatan layar.
    const render = async box => {
      if (box.dataset.state) return;
      box.dataset.state = 'busy';
      try {
        const page = await pdf.getPage(+box.dataset.n);
        const base = page.getViewport({scale: 1});
        const scale = box.clientWidth * Math.min(window.devicePixelRatio || 1, 2) / base.width;
        const vp = page.getViewport({scale});
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(vp.width); canvas.height = Math.round(vp.height);
        await page.render({canvasContext: canvas.getContext('2d'), viewport: vp}).promise;
        box.appendChild(canvas);
        box.classList.add('done');
      } catch (err) { console.error(err); box.dataset.state = ''; }
    };
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) render(e.target); }), {rootMargin: '900px 0px'});
    boxes.forEach(b => io.observe(b));

    // Nomor halaman yang sedang dibaca.
    const bar = viewer.querySelector('.rg-bar');
    new IntersectionObserver(es => es.forEach(e => bar.classList.toggle('show', e.isIntersecting))).observe(wrap);
    const seen = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) pgNow.textContent = pad(e.target.dataset.n); }), {rootMargin: '-45% 0px -45% 0px'});
    boxes.forEach(b => seen.observe(b));
  }).catch(err => { console.error('PDF gagal dimuat', err); useImages(); });
})();
</script>
@endif
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
