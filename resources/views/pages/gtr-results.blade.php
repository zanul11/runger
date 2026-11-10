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
  .rs-note{margin-top:12px;font-size:12px;color:var(--text-mute)}
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
    @if($published && count($categories))
      <div class="rs-bar">
        <div class="rs-tabs">
          @foreach($categories as $i => $c)
            <button type="button" @class(['on' => $i === 0]) data-rs-tab="{{ $c['id'] }}">{{ $c['distance'] }}</button>
          @endforeach
        </div>
        <div class="rs-tabs">
          <button type="button" class="on" data-rs-gender="">Semua</button>
          <button type="button" data-rs-gender="M">Putra</button>
          <button type="button" data-rs-gender="F">Putri</button>
        </div>
        <input type="search" class="rs-search" placeholder="Cari nama / BIB" data-rs-search>
      </div>
      <div data-rs-live>
        @php $hasNet = collect($categories)->contains(fn ($c) => collect($c['rows'])->whereNotNull('net')->isNotEmpty()); @endphp
        @foreach($categories as $i => $c)
          <div class="rs-table" data-rs-panel="{{ $c['id'] }}" @if($i !== 0) hidden @endif>
            <div class="rs-row rs-head"><span>#</span><span>BIB</span><span>Nama</span>@if($hasNet)<span class="hide-m">Net</span>@else<span class="hide-m"></span>@endif<span class="r">Waktu</span></div>
            @foreach($c['rows'] as $r)
              <a class="rs-row" href="{{ route('gtr.result', $r['bib']) }}" data-rs-q="{{ strtolower($r['name'] . ' ' . $r['bib']) }}" data-rs-g="{{ $r['gender'] }}">
                <span class="rk" data-rk="{{ $r['status'] === 'finisher' ? $r['rank'] : strtoupper($r['status']) }}" data-rk-g="{{ $r['status'] === 'finisher' ? $r['rank_gender'] : strtoupper($r['status']) }}">{{ $r['status'] === 'finisher' ? $r['rank'] : strtoupper($r['status']) }}</span>
                <span>{{ $r['bib'] }}</span>
                <span class="nm">{{ $r['name'] }}@if($r['club'])<small>{{ $r['club'] }}</small>@endif</span>
                <span class="hide-m tm" style="opacity:.6">{{ $hasNet && $r['status'] === 'finisher' ? ($r['net'] ?? '—') : '' }}</span>
                <span class="r tm">{{ $r['status'] === 'finisher' ? $r['gun'] : '—' }}</span>
              </a>
            @endforeach
          </div>
        @endforeach
      </div>
      <p class="rs-note">Waktu = gun time (semua kategori start bersama, dasar peringkat). Diperbarui otomatis tiap {{ \App\Http\Controllers\PageController::RESULTS_CACHE_SECONDS }} detik.</p>
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
    var state = { tab: null, gender: '', q: '' };
    var search = document.querySelector('[data-rs-search]');
    var first = document.querySelector('[data-rs-tab]');
    if (!first) return;
    state.tab = first.dataset.rsTab;

    function apply() {
      document.querySelectorAll('[data-rs-tab]').forEach(function (b) { b.classList.toggle('on', b.dataset.rsTab === state.tab); });
      document.querySelectorAll('[data-rs-gender]').forEach(function (b) { b.classList.toggle('on', b.dataset.rsGender === state.gender); });
      document.querySelectorAll('[data-rs-panel]').forEach(function (p) { p.hidden = p.dataset.rsPanel !== state.tab; });
      document.querySelectorAll('[data-rs-q]').forEach(function (r) {
        var ok = (!state.q || r.dataset.rsQ.indexOf(state.q) !== -1) && (!state.gender || r.dataset.rsG === state.gender);
        r.style.display = ok ? '' : 'none';
        var rk = r.querySelector('[data-rk]');
        rk.textContent = state.gender ? rk.dataset.rkG : rk.dataset.rk; // peringkat gender saat difilter
      });
    }

    document.addEventListener('click', function (e) {
      var t = e.target.closest('[data-rs-tab]'), g = e.target.closest('[data-rs-gender]');
      if (t) { state.tab = t.dataset.rsTab; apply(); }
      if (g) { state.gender = g.dataset.rsGender; apply(); }
    });
    if (search) search.addEventListener('input', function () { state.q = search.value.trim().toLowerCase(); apply(); });

    // Auto-refresh: ambil ulang halaman, ganti hanya tabel (tab, filter & pencarian tetap).
    setInterval(function () {
      if (document.hidden) return;
      fetch(location.href, { headers: { 'X-Requested-With': 'fetch' } })
        .then(function (r) { return r.ok ? r.text() : null; })
        .then(function (html) {
          if (!html) return;
          var fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-rs-live]');
          var live = document.querySelector('[data-rs-live]');
          if (fresh && live) { live.innerHTML = fresh.innerHTML; apply(); }
        })
        .catch(function () {});
    }, {{ \App\Http\Controllers\PageController::RESULTS_CACHE_SECONDS }} * 1000);
  })();
</script>
@endpush
