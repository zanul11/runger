@extends('layouts.gtr')

@section('title', 'Results — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@push('styles')
<style>
  .rs-bar{display:flex;flex-wrap:wrap;justify-content:space-between;gap:12px;margin-bottom:16px}
  .rs-tabs{display:inline-flex;gap:4px;padding:4px;border-radius:999px;background:var(--bg-soft);border:1px solid var(--line)}
  .rs-tabs button{padding:9px 18px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:var(--text-soft)}
  .rs-tabs button.on{background:#fff;color:#101010}
  .rs-search{flex:1;max-width:320px;min-width:180px;padding:11px 16px;border-radius:999px;border:1px solid var(--line-strong);background:var(--bg-soft);color:#fff;font:inherit;font-size:14px}
  .rs-table{border:1px solid var(--line);border-radius:18px;overflow:hidden}
  .rs-table[hidden]{display:none}
  .rs-row{display:grid;grid-template-columns:52px 70px 1fr 70px 100px;gap:10px;align-items:center;padding:12px 16px;border-top:1px solid var(--line);font-size:14px;transition:background .2s}
  a.rs-row:hover{background:rgba(255,255,255,.04)}
  .rs-head{border-top:none;background:var(--bg-soft);font-family:'Archivo',sans-serif;font-weight:800;font-size:10.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--text-mute)}
  .rs-row .r{text-align:right}
  .rs-row .rk{font-family:'Archivo',sans-serif;font-weight:900;font-size:16px}
  .rs-row .nm{font-weight:700;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .rs-row .nm small{display:block;font-weight:500;font-size:12px;color:var(--text-mute)}
  .rs-row .tm{font-family:'Archivo',sans-serif;font-weight:800;font-variant-numeric:tabular-nums}
  @media (max-width:640px){ .rs-row{grid-template-columns:40px 54px 1fr 84px;font-size:13px} .hide-m{display:none} }
</style>
@endpush

@section('content')
<section class="block" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Race Results</div>
      <h2>Results</h2>
      <p>{{ $published ? 'Hasil resmi Gerung Trail Run 2026. Klik nama untuk melihat split, e-sertifikat & kartu story.' : 'Hasil resmi dan klasemen akhir akan diumumkan di sini setelah race day.' }}</p>
    </div>
    @if($published && $categories->count())
      <div class="rs-bar">
        <div class="rs-tabs">
          @foreach($categories as $i => $c)
            <button type="button" @class(['on' => $i === $categories->keys()->first()]) data-rs-tab="{{ $c['cat']->id }}">{{ $c['cat']->distance }}</button>
          @endforeach
        </div>
        <input type="search" class="rs-search" placeholder="Cari nama / BIB" data-rs-search>
      </div>
      @foreach($categories as $i => $c)
        <div class="rs-table" data-rs-panel="{{ $c['cat']->id }}" @if($i !== $categories->keys()->first()) hidden @endif>
          <div class="rs-row rs-head"><span>#</span><span>BIB</span><span>Nama</span><span class="hide-m">Gender</span><span class="r">Waktu</span></div>
          @foreach($c['rows'] as $r)
            <a class="rs-row" href="{{ route('gtr.result', $r->registration->bib_number) }}" data-rs-q="{{ strtolower($r->registration->full_name . ' ' . $r->registration->bib_number) }}">
              <span class="rk">{{ $r->status === 'finisher' ? $r->rank_category : strtoupper($r->status) }}</span>
              <span>{{ $r->registration->bib_number }}</span>
              <span class="nm">{{ $r->registration->full_name }}@if($r->registration->club)<small>{{ $r->registration->club }}</small>@endif</span>
              <span class="hide-m">{{ $r->registration->genderCode() === 'F' ? 'P' : 'L' }}</span>
              <span class="r tm">{{ $r->status === 'finisher' ? $r->net_time_formatted : '—' }}</span>
            </a>
          @endforeach
        </div>
      @endforeach
    @else
    <div class="placeholder">
      <div class="ph-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
        </svg>
      </div>
      <div class="ph-title">Race Belum Dimulai</div>
      <div class="ph-sub">Klasemen finisher, chip time, dan medali certificate akan tersedia online dalam 24 jam setelah race day.</div>
      <div class="ph-badge"><span class="pulse"></span>29 Nov 2026</div>
    </div>
    @endif
  </div>
</section>
@endsection

@push('scripts')
<script>
  (function () {
    var tabs = document.querySelectorAll('[data-rs-tab]'), search = document.querySelector('[data-rs-search]');
    tabs.forEach(function (b) {
      b.addEventListener('click', function () {
        tabs.forEach(function (x) { x.classList.toggle('on', x === b); });
        document.querySelectorAll('[data-rs-panel]').forEach(function (p) { p.hidden = p.dataset.rsPanel !== b.dataset.rsTab; });
      });
    });
    if (search) search.addEventListener('input', function () {
      var q = search.value.trim().toLowerCase();
      document.querySelectorAll('[data-rs-q]').forEach(function (r) { r.style.display = !q || r.dataset.rsQ.indexOf(q) !== -1 ? '' : 'none'; });
    });
  })();
</script>
@endpush
