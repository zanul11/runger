@php
  $pct = $total ? (int) round($done / $total * 100) : 0;
  $fmt = fn ($dt) => $dt?->copy()->setTimezone('Asia/Makassar')->locale('id')->translatedFormat('j M H:i');
@endphp
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#e9eae5">
<meta name="color-scheme" content="light">
<meta name="robots" content="noindex, nofollow">
<title>Persiapan Panitia — GTR Crew</title>
<meta name="description" content="Checklist persiapan panitia Gerung Trail Run 2026: perlengkapan, pos, medis, racepack, dan lainnya.">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/gtr-scanner-192.png">
<link rel="apple-touch-icon" href="/pwa/gtr-scanner-apple-touch.png">
{{--
  Halaman publik BACA-SAJA (tanpa login) untuk seluruh panitia, bertema GTR Crew ("lime & ink",
  sama dengan resources/js/scanner/scanner.css). Mengubah checklist hanya dari GTR Crew (/crew)
  dengan akun panitia. Filter di browser; muat ulang otomatis tiap 60 detik (filter dipertahankan).
--}}
<style>
  :root{
    --bg:#e9eae5;--surface:#fff;--surface-2:#f5f5f1;--surface-3:#e1e2dc;--line:#d7d8d1;--line-strong:#aeb0a6;
    --text:#111;--muted:#46483f;--faint:#6b6d64;--ink:#141414;--ink-3:#333;--lime:#cdee4b;--lime-soft:#eef8c9;--lime-text:#3d5000;
    --r:20px;--r-lg:28px;--pad:12px;color-scheme:light;
  }
  *{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
  html,body{margin:0;background:var(--bg);color:var(--text);font:600 16px/1.35 system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",sans-serif;-webkit-font-smoothing:antialiased}
  button{font:inherit;color:inherit;cursor:pointer;border:0;background:none;padding:0}
  .num{font-variant-numeric:tabular-nums}
  [hidden]{display:none !important}
  .shell{max-width:560px;margin:0 auto;padding:max(10px,env(safe-area-inset-top)) var(--pad) calc(28px + env(safe-area-inset-bottom))}

  /* Header (seperti GTR Crew) */
  .top{display:flex;align-items:center;gap:10px;padding-bottom:8px}
  .logo-tile{flex:none;display:grid;place-items:center;width:62px;height:48px;padding:4px 6px;border-radius:14px;background:#fff}
  .logo-tile img{display:block;width:100%;height:100%;object-fit:contain}
  .brand{flex:1;min-width:0;line-height:1.1}
  .brand b{display:block;font-size:17px;font-weight:800;letter-spacing:-.015em}
  .brand small{display:block;color:var(--faint);font-size:12px;font-weight:650}
  .pill{flex:none;display:inline-flex;align-items:center;gap:7px;height:40px;padding:0 14px;border-radius:999px;background:var(--surface);font-size:13px;font-weight:750}
  .pill .icon{width:16px;height:16px}

  .locbar{display:flex;align-items:center;gap:12px;padding:10px;border-radius:var(--r-lg);background:var(--ink);color:#fff}
  .loc-badge{flex:none;display:grid;place-items:center;width:50px;height:50px;border-radius:18px;background:var(--lime);color:var(--ink)}
  .loc-badge .icon{width:24px;height:24px}
  .loc-text small{display:block;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;opacity:.7}
  .loc-text strong{display:block;font-size:20px;font-weight:800;letter-spacing:-.01em;line-height:1.15}

  .card{margin-top:10px;display:grid;gap:10px;padding:14px;border-radius:var(--r-lg);background:var(--surface)}
  .meter{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:6px;align-items:center}
  .meter span{color:var(--muted);font-size:13px;font-weight:750}
  .meter b{font-size:16px;font-weight:800}
  .bar{grid-column:span 2;height:8px;border-radius:4px;background:var(--surface-3);overflow:hidden}
  .bar i{display:block;height:100%;border-radius:4px;background:var(--ink)}
  .meta{display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;color:var(--faint);font-size:12.5px;font-weight:650}
  .meta button{padding:6px 12px;border-radius:999px;background:var(--surface-2);color:var(--text);font-size:12.5px;font-weight:800}

  /* Cari & filter: menempel di atas saat scroll */
  .tools{position:sticky;top:0;z-index:5;margin:10px calc(-1 * var(--pad)) 0;padding:8px var(--pad) 6px;background:var(--bg)}
  .search{display:flex;align-items:center;gap:10px;min-height:54px;padding:0 16px;border-radius:999px;background:var(--surface);color:var(--faint)}
  .search:focus-within{color:var(--ink);box-shadow:0 0 0 4px var(--lime-soft)}
  .search .icon{width:20px;height:20px}
  .search input{flex:1;min-width:0;border:0;outline:0;background:transparent;color:var(--text);font:inherit;font-size:17px;font-weight:650}
  .search input::placeholder{color:#8d8f85}
  .chips{display:flex;gap:6px;margin:8px calc(-1 * var(--pad)) 0;padding:0 var(--pad) 2px;overflow-x:auto;scrollbar-width:none}
  .chips::-webkit-scrollbar{display:none}
  .chip{flex:none;min-height:40px;padding:0 16px;border-radius:999px;background:var(--surface);color:var(--muted);font-size:14px;font-weight:750;white-space:nowrap}
  .chip[aria-pressed="true"]{background:var(--lime);color:var(--ink)}
  .sep{flex:none;width:2px;margin:8px 2px;border-radius:1px;background:var(--line-strong)}

  .section-title{display:flex;justify-content:space-between;margin:16px 6px 8px;font-size:12px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint)}
  /* Akordeon kategori: daftar kategori + progres dulu, ketuk untuk membuka item. */
  .groups{display:grid;gap:8px}
  .group{border-radius:var(--r-lg);background:var(--surface);overflow:hidden}
  .cathead{display:flex;align-items:center;gap:12px;width:100%;padding:14px 14px 14px 16px;text-align:left}
  .cathead:active{background:var(--lime-soft)}
  .cathead .grow{flex:1;min-width:0}
  .cathead b{display:block;font-size:16px;font-weight:800;line-height:1.2}
  .cathead small{display:block;margin-top:2px;color:var(--faint);font-size:12.5px;font-weight:650}
  .catbar{display:block;height:6px;margin-top:8px;border-radius:3px;background:var(--surface-3);overflow:hidden}
  .catbar i{display:block;height:100%;border-radius:3px;background:var(--ink)}
  .group.complete .catbar i{background:#b8dc2c}
  .catpct{flex:none;font-size:17px;font-weight:800}
  .group.complete .catpct{color:var(--lime-text)}
  .catchev{color:var(--faint);transition:transform .15s ease}
  .catchev .icon{width:20px;height:20px}
  .group.open .catchev{transform:rotate(90deg)}
  body.filtering .catchev{display:none}
  .list{list-style:none;margin:0;padding:0 8px 8px;display:grid;gap:6px}
  .item{display:flex;align-items:flex-start;gap:12px;padding:10px 12px 10px 10px;border-radius:var(--r);background:var(--surface-2)}
  .item.done{background:var(--bg)}
  .ck{flex:none;display:grid;place-items:center;width:28px;height:28px;margin:6px;border-radius:50%;border:2px solid var(--line-strong);color:var(--ink)}
  .item.done .ck{border-color:var(--lime);background:var(--lime)}
  .ck .icon{width:16px;height:16px}
  .body{min-width:0;padding-top:2px}
  .body b{display:block;font-size:15px;font-weight:750;line-height:1.25;overflow-wrap:anywhere}
  .item.done .body b{color:var(--faint);text-decoration:line-through}
  .body small{display:block;margin-top:2px;color:var(--faint);font-size:12.5px;font-weight:650;overflow-wrap:anywhere}
  .body .note{color:var(--muted);font-weight:600;white-space:pre-line}
  .body .by{color:var(--lime-text);font-weight:750}
  .empty{margin:12px 0 0;padding:16px;text-align:center;color:var(--faint);border:1.5px dashed var(--line-strong);border-radius:var(--r);font-size:14px}
  .tip{display:flex;gap:10px;align-items:flex-start;margin-top:16px;padding:12px 14px;border-radius:var(--r);background:var(--surface);color:var(--muted);font-size:13px;font-weight:600}
  .tip .icon{flex:none;width:18px;height:18px;margin-top:1px;color:var(--ink)}
  .tip a{color:var(--text);font-weight:800}
  .icon{flex:none;display:block}
</style>
</head>
<body>
@php
  // Ikon garis (gaya Lucide, sama dengan GTR Crew).
  $icon = fn (string $paths) => '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
  $check = '<path d="M20 6 9 17l-5-5"/>';
@endphp
<div class="shell">
  <header class="top">
    <span class="logo-tile"><img src="{{ asset('gtr/logo-gtr2.png') }}" alt="Gerung Trail Run — Keteri Hill"></span>
    <div class="brand">
      <b>GTR Crew</b>
      <small>Persiapan · GTR 2026</small>
    </div>
    <span class="pill">{!! $icon('<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>') !!} Lihat saja</span>
  </header>

  <section class="locbar">
    <div class="loc-badge">{!! $icon('<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/>') !!}</div>
    <div class="loc-text">
      <small>Panitia</small>
      <strong>Persiapan Panitia</strong>
    </div>
  </section>

  <section class="card">
    <div class="meter">
      <span>Selesai</span>
      <b class="num">{{ $done }} / {{ $total }} · {{ $pct }}%</b>
      <div class="bar"><i style="width: {{ $pct }}%"></i></div>
    </div>
    <div class="meta">
      @if ($updatedAt)<span>Diperbarui {{ $fmt($updatedAt) }} WITA</span>@endif
      <button type="button" onclick="location.reload()">Muat ulang</button>
    </div>
  </section>

  @if ($total === 0)
    <p class="empty">Checklist persiapan belum diisi.</p>
  @else
    <div class="tools">
      <label class="search">
        {!! $icon('<circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>') !!}
        <input id="prp-q" type="search" placeholder="Cari item, PIC, lokasi" autocomplete="off" aria-label="Cari">
      </label>
      <div class="chips" role="group" aria-label="Filter">
        <button type="button" class="chip" data-phase="all" aria-pressed="true">Semua</button>
        @foreach ($phases as $key => $label)
          <button type="button" class="chip" data-phase="{{ $key }}" aria-pressed="false">{{ $label }}</button>
        @endforeach
        <span class="sep"></span>
        <button type="button" class="chip" id="prp-open" aria-pressed="false">Belum selesai</button>
      </div>
    </div>

    <div class="section-title"><span id="prp-head">Kategori</span><span class="num">{{ $groups->count() }}</span></div>
    <div class="groups">
    @foreach ($groups as $category => $items)
      @php
        $gDone = $items->whereNotNull('done_at')->count();
        $gTotal = $items->count();
        $gPct = $gTotal ? (int) round($gDone / $gTotal * 100) : 0;
      @endphp
      <section class="group {{ $gTotal && $gDone === $gTotal ? 'complete' : '' }}" data-cat="{{ $category }}">
        <button type="button" class="cathead" aria-expanded="false">
          <span class="grow">
            <b>{{ $category }}</b>
            <small class="num">{{ $gDone }}/{{ $gTotal }} selesai @if ($gTotal - $gDone) · {{ $gTotal - $gDone }} belum @endif</small>
            <span class="catbar"><i style="width: {{ $gPct }}%"></i></span>
          </span>
          <b class="catpct num">{{ $gPct }}%</b>
          <span class="catchev">{!! $icon('<path d="M9 18l6-6-6-6"/>') !!}</span>
        </button>
        <ul class="list" hidden>
          @foreach ($items as $i)
            @php
              $meta = collect([$i->qty, $i->location, $i->pic ? 'PIC '.$i->pic : null, $phases[$i->phase] ?? null])->filter()->join(' · ');
              $search = mb_strtolower(collect([$i->title, $i->qty, $i->location, $i->pic, $category, $i->notes])->filter()->join(' '));
            @endphp
            <li class="item {{ $i->done_at ? 'done' : '' }}" data-phase="{{ $i->phase }}" data-done="{{ $i->done_at ? 1 : 0 }}" data-q="{{ $search }}">
              <span class="ck" aria-label="{{ $i->done_at ? 'Selesai' : 'Belum' }}">@if ($i->done_at){!! $icon($check) !!}@endif</span>
              <div class="body">
                <b>{{ $i->title }}</b>
                @if ($meta)<small>{{ $meta }}</small>@endif
                @if ($i->notes)<small class="note">{{ $i->notes }}</small>@endif
                @if ($i->done_at)<small class="by">✓ {{ $i->doneBy?->name ?? 'Panitia' }} · {{ $fmt($i->done_at) }}</small>@endif
              </div>
            </li>
          @endforeach
        </ul>
      </section>
    @endforeach
    </div>
    <p class="empty" id="prp-none" hidden>Tidak ada item yang cocok.</p>
  @endif

  <p class="tip">
    {!! $icon('<rect x="5" y="11" width="14" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>') !!}
    <span>Halaman ini hanya untuk dilihat. Untuk mencentang atau mengubah item, buka <a href="{{ url('/crew/app') }}">GTR Crew</a> dan login dengan akun <b>panitia</b>.</span>
  </p>
</div>

<script>
(() => {
  const items = [...document.querySelectorAll('.item')];
  if (!items.length) return;
  const q = document.getElementById('prp-q');
  const openBtn = document.getElementById('prp-open');
  const chips = [...document.querySelectorAll('.chip[data-phase]')];
  const KEY = 'gtr-prep-filter';
  let state = { phase: 'all', open: false, q: '', cats: [] };
  try { state = { ...state, ...JSON.parse(sessionStorage.getItem(KEY) || '{}') }; } catch (e) {}

  const groups = [...document.querySelectorAll('.group')];

  function apply() {
    const term = state.q.trim().toLowerCase();
    // Sedang cari / filter → kategori tanpa hasil disembunyikan, yang ada hasilnya terbuka otomatis.
    const filtering = !!term || state.phase !== 'all' || state.open;
    let shown = 0;
    for (const li of items) {
      const ok = (state.phase === 'all' || li.dataset.phase === state.phase)
        && (!state.open || li.dataset.done === '0')
        && (!term || li.dataset.q.includes(term));
      li.hidden = !ok;
      if (ok) shown++;
    }
    for (const g of groups) {
      const any = [...g.querySelectorAll('.item')].some((li) => !li.hidden);
      const open = filtering ? any : state.cats.includes(g.dataset.cat);
      g.hidden = filtering && !any;
      g.classList.toggle('open', open);
      g.querySelector('.list').hidden = !open;
      g.querySelector('.cathead').setAttribute('aria-expanded', String(open));
    }
    document.body.classList.toggle('filtering', filtering);
    document.getElementById('prp-head').textContent = filtering ? 'Hasil' : 'Kategori';
    document.getElementById('prp-none').hidden = shown > 0;
    chips.forEach((c) => c.setAttribute('aria-pressed', String(c.dataset.phase === state.phase)));
    openBtn.setAttribute('aria-pressed', String(state.open));
    try { sessionStorage.setItem(KEY, JSON.stringify(state)); } catch (e) {}
  }

  q.value = state.q;
  q.addEventListener('input', () => { state.q = q.value; apply(); });
  chips.forEach((c) => c.addEventListener('click', () => { state.phase = c.dataset.phase; apply(); }));
  openBtn.addEventListener('click', () => { state.open = !state.open; apply(); });
  for (const g of groups) {
    g.querySelector('.cathead').addEventListener('click', () => {
      if (document.body.classList.contains('filtering')) return;
      const c = g.dataset.cat;
      state.cats = state.cats.includes(c) ? state.cats.filter((x) => x !== c) : [...state.cats, c];
      apply();
    });
  }
  apply();

  // Muat ulang otomatis tiap 60 detik (data terbaru), kecuali sedang mengetik di pencarian.
  setInterval(() => {
    if (document.visibilityState === 'visible' && document.activeElement !== q) location.reload();
  }, 60000);
})();
</script>
</body>
</html>
