@extends('layouts.runner-app')
@section('title', 'Race Guide — Runger')
@section('app-title', 'Race Guide')

@push('styles')
@verbatim
<style>
  .back{display:inline-flex;align-items:center;gap:6px;margin-bottom:14px;font-weight:700;font-size:12.5px;color:var(--blue)}
  .rg-meta{font-weight:700;font-size:10.5px;letter-spacing:.08em;text-transform:uppercase;color:var(--mute);margin:-10px 0 14px}
  .rg-acts{display:flex;gap:8px;margin-bottom:16px}
  .rg-acts a{flex:1;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:11px 12px;border-radius:12px;
    font-weight:800;font-size:11.5px;letter-spacing:.04em;text-transform:uppercase;border:1px solid var(--line);color:var(--blue);background:#fff}
  .rg-acts a.main{background:var(--red);border-color:var(--red);color:#fff;box-shadow:0 8px 18px rgba(229,57,53,.3)}
  .rg-acts svg{width:15px;height:15px}

  .rg-pages{display:flex;flex-direction:column;gap:12px}
  .rg-page{position:relative;display:block;border-radius:14px;overflow:hidden;background:#141414;border:1px solid var(--line);aspect-ratio:var(--ar,1080/1350);box-shadow:0 6px 20px rgba(15,24,48,.08)}
  .rg-page canvas,.rg-page img{display:block;width:100%;height:100%}
  .rg-page .ld{position:absolute;inset:0;display:grid;place-items:center;font-weight:700;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.5)}
  .rg-page.done .ld{display:none}
  .rg-fallback[hidden]{display:none}
  .rg-pg{position:fixed;left:50%;bottom:calc(84px + env(safe-area-inset-bottom));z-index:25;transform:translate(-50%,20px);opacity:0;pointer-events:none;
    transition:transform .25s,opacity .25s;padding:7px 14px;border-radius:999px;background:rgba(15,24,48,.88);color:#fff;
    font-weight:800;font-size:11px;letter-spacing:.12em;box-shadow:0 8px 22px rgba(15,24,48,.3)}
  .rg-pg.show{transform:translate(-50%,0);opacity:1}
</style>
@endverbatim
@endpush

@section('content')
<a class="back" href="{{ route('gtr.account.guide') }}">← Race Guide &amp; Dokumen</a>
<div class="sec-title">Official Race Guide</div>
<div class="sec-sub">Simpan di HP biar tetap bisa dibuka saat race day.</div>
<div class="rg-meta">{{ $slides->count() }} halaman{{ $updatedAt ? ' · diperbarui ' . $updatedAt->timezone('Asia/Makassar')->translatedFormat('d M Y, H:i') . ' WITA' : '' }}</div>

<div class="rg-acts">
  @if($pdfUrl)
    <a class="main" href="{{ route('gtr.guide.pdf') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
      Unduh PDF
    </a>
    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/></svg>
      Buka
    </a>
  @elseif($hasZip)
    <a class="main" href="{{ route('gtr.guide.zip') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>
      Unduh (ZIP)
    </a>
  @endif
</div>

@if($pdfUrl)
  <div id="rg-viewer" data-src="{{ $pdfUrl }}">
    <div class="rg-pages" id="rg-pages">
      @foreach($slides as $i => $url)
        <div class="rg-page"><span class="ld">Memuat halaman {{ $i + 1 }}…</span></div>
      @endforeach
    </div>
  </div>
@endif

{{-- Cadangan bila PDF tidak tersedia / PDF.js gagal dimuat: slide sebagai gambar. --}}
<div class="rg-fallback" id="rg-fallback" @if($pdfUrl) hidden @endif>
  <div class="rg-pages">
    @foreach($slides as $i => $url)
      <a class="rg-page done" href="{{ $url }}" target="_blank" rel="noopener" aria-label="Buka halaman {{ $i + 1 }}">
        <img src="{{ $url }}" alt="Race Guide halaman {{ $i + 1 }}" loading="{{ $i < 2 ? 'eager' : 'lazy' }}" decoding="async" width="1080" height="1350">
      </a>
    @endforeach
  </div>
</div>
<div class="rg-pg" id="rg-pg">01 / {{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</div>
@endsection

@if($pdfUrl)
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
(function () {
  const viewer = document.getElementById('rg-viewer');
  const fallback = document.getElementById('rg-fallback');
  const useImages = () => { viewer.remove(); fallback.hidden = false; };
  if (!window.pdfjsLib) { useImages(); return; }
  pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

  const wrap = document.getElementById('rg-pages');
  const badge = document.getElementById('rg-pg');
  const pad = n => String(n).padStart(2, '0');

  pdfjsLib.getDocument(viewer.dataset.src).promise.then(async pdf => {
    const first = (await pdf.getPage(1)).getViewport({scale: 1});
    wrap.innerHTML = '';
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

    // Render saat mendekati layar, tajam sesuai lebar tampilan & kepadatan layar.
    const render = async box => {
      if (box.dataset.state) return;
      box.dataset.state = 'busy';
      try {
        const page = await pdf.getPage(+box.dataset.n);
        const base = page.getViewport({scale: 1});
        const vp = page.getViewport({scale: box.clientWidth * Math.min(window.devicePixelRatio || 1, 2) / base.width});
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(vp.width); canvas.height = Math.round(vp.height);
        await page.render({canvasContext: canvas.getContext('2d'), viewport: vp}).promise;
        box.appendChild(canvas);
        box.classList.add('done');
      } catch (err) { console.error(err); box.dataset.state = ''; }
    };
    const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) render(e.target); }), {rootMargin: '700px 0px'});
    boxes.forEach(b => io.observe(b));

    // Penanda halaman yang sedang dibaca (melayang di atas tab bar).
    new IntersectionObserver(es => es.forEach(e => badge.classList.toggle('show', e.isIntersecting))).observe(wrap);
    const seen = new IntersectionObserver(es => es.forEach(e => {
      if (e.isIntersecting) badge.textContent = pad(e.target.dataset.n) + ' / ' + pad(pdf.numPages);
    }), {rootMargin: '-45% 0px -45% 0px'});
    boxes.forEach(b => seen.observe(b));
  }).catch(err => { console.error('PDF gagal dimuat', err); useImages(); });
})();
</script>
@endpush
@endif
