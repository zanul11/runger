@extends('layouts.gtr-preview')

@section('title', 'Race Guide — GTR Admin')

@php
  $cats = collect($g['categories']);
  $hasRacepack = $g['racepack']['location'] || $g['racepack']['schedule'] || $g['racepack']['notes'] || $g['racepack']['address'] || $g['racepack']['qr'] || $g['racepack']['directions'];
  $infoCats = $cats->filter(fn ($c) => $c['pois'])->values();
  $awardCats = $cats->filter(fn ($c) => $c['awards'])->values();

  // Aturan dipecah per slide berdasarkan panjang teks agar tetap terbaca.
  $ruleChunks = [];
  $chunk = []; $budget = 0;
  foreach ($g['rules'] as $i => $r) {
      $cost = 90 + mb_strlen($r['title']) + mb_strlen($r['content']);
      if ($chunk && ($budget + $cost > 1250 || count($chunk) >= 5)) { $ruleChunks[] = $chunk; $chunk = []; $budget = 0; }
      $chunk[] = $r + ['n' => $i + 1];
      $budget += $cost;
  }
  if ($chunk) $ruleChunks[] = $chunk;

  // Pola garis kontur (topografi) untuk latar slide, dibuat sekali sebagai data URI.
  $topo = '';
  foreach ([[180, 260, 1.0], [930, 980, 1.3], [760, 120, 0.8]] as [$cx, $cy, $seed]) {
      for ($r = 1; $r <= 9; $r++) {
          $pts = [];
          for ($a = 0; $a < 360; $a += 10) {
              $t = deg2rad($a);
              $rad = $r * 44 * (1 + 0.16 * sin(3 * $t + $r * 0.7 * $seed) + 0.07 * sin(5 * $t + $seed * 2));
              $pts[] = round($cx + cos($t) * $rad * 1.25, 1) . ' ' . round($cy + sin($t) * $rad, 1);
          }
          $topo .= '<path d="M' . implode(' L', $pts) . ' Z"/>';
      }
  }
  $topoUri = 'data:image/svg+xml;charset=utf-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="1080" height="1350" viewBox="0 0 1080 1350"><g fill="none" stroke="#ffffff" stroke-opacity=".055" stroke-width="1.6" stroke-linejoin="round">' . $topo . '</g></svg>');

  $pointColor = ['ws' => '#3B82F6', 'cp' => '#F59E0B', 'cop' => '#E53935'];
  $hasRpc = $g['rpc']['requirements'] || $g['rpc']['proxy'];
  $hasVenue = $g['venue']['name'] || $g['venue']['directions'] || $g['venue']['address'];
  $hasParking = $g['parking']['name'] || $g['parking']['notes'];

  // Detail titik pos per kategori: Start → WS/CP/COP → Finish, dipecah maks. 5 baris per slide.
  $stopSlides = [];
  foreach ($cats as $c) {
      if (! $c['points']) continue;
      $km = fn ($v) => 'KM ' . str_replace('.', ',', rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'));
      $rows = [['code' => 'START', 'type' => 'start', 'km' => $km(0), 'title' => 'Start', 'location' => $g['event']['location'], 'facilities' => [], 'notes' => null, 'cutoff' => null, 'time' => $c['start']]];
      foreach ($c['points'] as $p) {
          $rows[] = ['code' => $p['code'], 'type' => $p['type'], 'km' => $km($p['km']), 'title' => $p['title'], 'location' => $p['location'], 'facilities' => $p['facilities'], 'notes' => $p['notes'] ?? null, 'cutoff' => $p['cutoff'], 'time' => null];
      }
      $rows[] = ['code' => 'FINISH', 'type' => 'finish', 'km' => $c['km'] ? $km($c['km']) : '', 'title' => 'Finish', 'location' => null, 'facilities' => [], 'notes' => null, 'cutoff' => null, 'time' => $c['cot'] ? 'COT ' . $c['cot'] : null];
      // Dibagi rata (mis. 7 baris → 4 + 3), bukan 5 + 2 yang menyisakan slide hampir kosong.
      $parts = array_chunk($rows, (int) ceil(count($rows) / max(1, ceil(count($rows) / 5))));
      foreach ($parts as $i => $part) {
          $stopSlides[] = ['cat' => $c, 'rows' => $part, 'part' => $i + 1, 'parts' => count($parts)];
      }
  }
  $stopColor = $pointColor + ['start' => '#22C55E', 'finish' => '#FFFFFF'];
  $slideNo = 0;
@endphp

@push('styles')
<style>
  /* ===== Panel kontrol (di luar slide, tidak ikut diekspor) ===== */
  .rg-wrap{max-width:1320px;margin:0 auto;padding:20px 20px 60px}
  .rg-bar{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px;padding:14px 18px;margin-bottom:14px;border-radius:18px;background:#101010;color:#fff;box-shadow:0 10px 30px rgba(0,0,0,.18)}
  .rg-bar h1{font-family:'Archivo',sans-serif;font-weight:900;font-size:18px;text-transform:uppercase;margin-right:auto}
  .rg-bar h1 small{display:block;font-size:10.5px;letter-spacing:.14em;color:rgba(255,255,255,.55);font-weight:800}
  .rg-bar label{display:flex;align-items:center;gap:8px;font-size:12.5px;font-weight:700}
  .rg-bar select{padding:9px 10px;border-radius:10px;border:1px solid rgba(255,255,255,.18);background:#1C1C1C;color:#fff;font:inherit;font-size:12.5px}
  .rg-bar input[type=checkbox]{width:16px;height:16px;accent-color:#E53935}
  .rg-btn{padding:11px 16px;border-radius:12px;border:none;background:#E53935;color:#fff;font-family:'Archivo',sans-serif;font-weight:800;font-size:12.5px;letter-spacing:.04em;cursor:pointer}
  .rg-btn.ghost{background:transparent;border:1px solid rgba(255,255,255,.25)}
  .rg-btn:disabled{opacity:.55;cursor:wait}
  .rg-status{flex-basis:100%;font-size:12.5px;color:rgba(255,255,255,.7);min-height:0}
  .rg-status:empty{display:none}
  .rg-pub{flex-basis:100%;display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.12);font-size:12.5px;color:rgba(255,255,255,.72)}
  .rg-pub a{color:#fff;font-weight:800;text-decoration:underline}
  .rg-pub-st{font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.6)}
  .rg-pub-st.on{color:#4ADE80}
  .rg-missing{margin-bottom:14px;padding:12px 16px;border-radius:14px;background:#FFF7E6;border:1px solid #F5D9A6;color:#8A5A00;font-size:13px}
  .rg-missing b{display:block;margin-bottom:4px}
  .rg-missing li{margin-left:18px;line-height:1.6}
  .rg-grid{display:grid;gap:18px;grid-template-columns:repeat(auto-fill,minmax(340px,1fr))}
  .rg-item{background:#fff;border:1px solid #E2E2E2;border-radius:16px;padding:10px;transition:opacity .2s}
  .rg-item.off{opacity:.4}
  .rg-item-bar{display:flex;align-items:center;gap:8px;margin-bottom:8px;font-size:12.5px;font-weight:700;color:#444}
  .rg-item-bar .nm{margin-right:auto;font-family:'Archivo',sans-serif;font-weight:800;text-transform:uppercase;letter-spacing:.04em}
  .rg-item-bar button{padding:7px 12px;border-radius:9px;border:1px solid #DADADA;background:#fff;font-weight:800;font-size:12px;cursor:pointer}
  .rg-item-bar input{width:15px;height:15px;accent-color:#E53935}
  .rg-frame{position:relative;width:100%;aspect-ratio:1080/1350;overflow:hidden;border-radius:10px;background:#0A0A0A}
  .rg-frame > .slide{position:absolute;left:0;top:0;transform-origin:0 0}
  body.editing .slide [data-edit]{outline:2px dashed rgba(229,57,53,.0);cursor:text}
  body.editing .slide [data-edit]:hover{outline-color:rgba(229,57,53,.7)}
  body.editing .slide [data-edit]:focus{outline:2px solid #E53935}

  /* ===== Slide 1080×1350 ===== */
  .slide{
    --red:#E53935;--soft:#C9C9C9;--mute:#8C8C8C;--line:rgba(255,255,255,.1);--card:#151515;
    width:1080px;height:1350px;overflow:hidden;position:relative;color:#fff;
    font-family:'Plus Jakarta Sans',sans-serif;
    background:
      radial-gradient(900px 560px at 0% 0%,rgba(229,57,53,.24),transparent 70%),
      radial-gradient(700px 520px at 100% 78%,rgba(229,57,53,.10),transparent 70%),
      #0A0A0A;
  }
  .slide .s-topo{position:absolute;inset:0;background-repeat:no-repeat;background-size:100% 100%;pointer-events:none}
  .s-head{position:absolute;left:72px;right:72px;top:60px;display:flex;align-items:center;justify-content:space-between;z-index:3}
  .s-brand{display:flex;align-items:center;gap:20px}
  .s-logo{display:block;background:#fff;border-radius:14px;padding:8px 10px}
  .s-logo img{display:block;height:68px;width:auto}
  .s-brand-txt{display:flex;flex-direction:column;gap:4px;padding-left:20px;border-left:2px solid rgba(255,255,255,.25)}
  .s-brand-txt b{font-family:'Archivo',sans-serif;font-weight:900;font-size:30px;line-height:1;text-transform:uppercase;letter-spacing:-.01em}
  .s-brand-txt small{font-family:'Archivo',sans-serif;font-weight:800;font-size:15px;letter-spacing:.16em;text-transform:uppercase;color:var(--red)}
  .s-runger{height:30px;width:auto;opacity:.9}

  .s-foot{position:absolute;left:0;right:0;bottom:0;height:190px;z-index:1}
  .s-ridge{position:absolute;inset:0;width:100%;height:100%}
  .s-foot-row{position:absolute;left:72px;right:72px;bottom:34px;display:flex;align-items:center;justify-content:space-between}
  .s-foot-txt{font-family:'Archivo',sans-serif;font-weight:800;font-size:17px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.7)}
  .s-pg{font-family:'Archivo',sans-serif;font-weight:800;font-size:20px;color:rgba(255,255,255,.55);letter-spacing:.06em}
  .s-pg b{color:#fff;font-weight:900}

  .s-main{position:absolute;left:72px;right:72px;top:212px;bottom:170px;display:flex;flex-direction:column;z-index:2}
  .s-eye{display:flex;align-items:center;gap:14px;font-family:'Archivo',sans-serif;font-weight:800;font-size:19px;letter-spacing:.2em;text-transform:uppercase;color:var(--soft)}
  .s-eye::before{content:'';width:44px;height:3px;background:var(--red);border-radius:2px}
  .s-title{font-family:'Archivo',sans-serif;font-weight:900;font-size:104px;line-height:.9;letter-spacing:-.025em;text-transform:uppercase;margin:16px 0 0}
  .s-title em{font-style:normal;color:var(--red)}
  .s-fit{flex:1;min-height:0;margin-top:40px;overflow:hidden}
  .s-fit-in{transform-origin:0 0}

  .s-lead{font-size:29px;line-height:1.45;color:var(--soft)}
  .s-card{background:var(--card);border:1px solid var(--line);border-radius:22px;padding:26px 30px}
  .s-lbl{font-family:'Archivo',sans-serif;font-weight:800;font-size:16px;letter-spacing:.18em;text-transform:uppercase;color:var(--red)}
  .s-val{font-family:'Archivo',sans-serif;font-weight:800;font-size:38px;line-height:1.15;margin-top:8px}
  .s-list{list-style:none;display:flex;flex-direction:column;gap:18px}
  .s-list li{position:relative;padding-left:40px;font-size:29px;line-height:1.4;color:#E6E6E6}
  .s-list li::before{content:'';position:absolute;left:4px;top:15px;width:14px;height:14px;background:var(--red);transform:rotate(45deg);border-radius:2px}
  .s-h3{font-family:'Archivo',sans-serif;font-weight:900;font-size:26px;letter-spacing:.12em;text-transform:uppercase;color:#fff;margin:38px 0 18px}
  .s-dot{display:inline-block;width:18px;height:18px;border-radius:50%;flex-shrink:0}

  /* Cover */
  .s-cover-img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}
  .s-cover-shade{position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,10,10,.55) 0%,rgba(10,10,10,.15) 26%,rgba(10,10,10,.55) 48%,#0A0A0A 74%)}
  .s-cover{position:absolute;left:72px;right:72px;bottom:84px;z-index:2}
  .s-cover .s-title{font-size:150px;margin-top:22px}
  .s-cover .s-lead{max-width:860px;margin-top:28px;color:#E2E2E2}
  .s-chips{display:flex;flex-wrap:wrap;gap:12px;margin-top:34px}
  .s-chip{display:inline-flex;align-items:center;gap:12px;padding:14px 22px;border-radius:999px;border:1.5px solid rgba(255,255,255,.22);background:rgba(10,10,10,.55);font-family:'Archivo',sans-serif;font-weight:700;font-size:22px}
  .s-chip svg{width:24px;height:24px;color:var(--red);flex-shrink:0}
  .s-swipe{display:inline-flex;align-items:center;gap:16px;margin-top:40px;padding:20px 30px;border-radius:999px;background:var(--red);font-family:'Archivo',sans-serif;font-weight:800;font-size:24px;letter-spacing:.14em;text-transform:uppercase}
  .s-swipe i{display:grid;place-items:center;width:38px;height:38px;border-radius:50%;background:#fff;color:var(--red);font-style:normal;font-size:22px;letter-spacing:0}

  /* Event timeline */
  .s-tl{display:flex;flex-direction:column;gap:30px}
  .s-tl-date{display:flex;align-items:center;gap:16px;margin-bottom:14px}
  .s-tl-date .n{font-family:'Archivo',sans-serif;font-weight:900;font-size:18px;letter-spacing:.16em;text-transform:uppercase;padding:9px 16px;border-radius:10px;background:#1E1E1E;border:1px solid var(--line);color:var(--soft)}
  .s-tl-date b{font-family:'Archivo',sans-serif;font-weight:900;font-size:34px;text-transform:uppercase;letter-spacing:-.005em}
  .s-tl-date .rd{margin-left:auto;font-family:'Archivo',sans-serif;font-weight:900;font-size:17px;letter-spacing:.16em;text-transform:uppercase;padding:9px 16px;border-radius:999px;background:var(--red)}
  .s-tl-day.race .s-tl-date .n{background:var(--red);border-color:var(--red);color:#fff}
  .s-tl-items{position:relative;margin-left:14px;padding-left:40px;border-left:3px solid rgba(255,255,255,.14);display:flex;flex-direction:column;gap:16px}
  .s-tl-day.race .s-tl-items{border-left-color:rgba(229,57,53,.55)}
  .s-tl-it{position:relative;display:grid;grid-template-columns:250px 1fr;gap:24px;align-items:start;background:var(--card);border:1px solid var(--line);border-radius:18px;padding:20px 24px}
  .s-tl-it::before{content:'';position:absolute;left:-52px;top:28px;width:18px;height:18px;border-radius:50%;background:#0A0A0A;border:4px solid var(--red)}
  .s-tl-it .t{font-family:'Archivo',sans-serif;font-weight:900;font-size:30px;line-height:1.15;color:var(--red);white-space:nowrap}
  .s-tl-it .d b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:30px;line-height:1.15}
  .s-tl-it .d span{display:block;font-size:22px;line-height:1.35;color:var(--soft);margin-top:6px}
  .s-tl-cats{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
  .s-tl-cats i{font-style:normal;font-family:'Archivo',sans-serif;font-weight:900;font-size:17px;padding:5px 11px;border-radius:8px;background:var(--c);color:#fff}
  .s-tl-note{margin-top:26px;font-family:'Archivo',sans-serif;font-weight:800;font-size:17px;letter-spacing:.14em;text-transform:uppercase;color:var(--mute)}

  /* Race pack */
  .s-pair{display:grid;grid-template-columns:1fr 1fr;gap:20px}
  .s-pair.one{grid-template-columns:1fr}

  /* Tabel (race day & gear) */
  .s-table{width:100%;border-collapse:separate;border-spacing:0;border:1px solid var(--line);border-radius:22px;overflow:hidden;background:var(--card)}
  .s-table th{background:var(--red);color:#fff;font-family:'Archivo',sans-serif;font-weight:900;font-size:22px;letter-spacing:.08em;text-transform:uppercase;padding:22px 24px;text-align:center}
  .s-table th:first-child{text-align:left}
  .s-table td{padding:22px 24px;border-top:1px solid var(--line);font-size:27px;line-height:1.3;text-align:center;vertical-align:middle}
  .s-table td:first-child{text-align:left}
  .s-table tr:nth-child(even) td{background:rgba(255,255,255,.025)}
  .s-table .cat{display:flex;align-items:center;gap:14px;font-family:'Archivo',sans-serif;font-weight:900;font-size:34px}
  .s-table .cat small{display:block;font-family:'Plus Jakarta Sans',sans-serif;font-weight:600;font-size:19px;color:var(--mute);letter-spacing:0}
  .s-table .big{font-family:'Archivo',sans-serif;font-weight:800;font-size:32px}
  .s-check{width:44px;height:44px;display:inline-block;vertical-align:middle}
  .s-no{color:rgba(255,255,255,.25);font-size:30px;font-weight:700}

  /* Kategori */
  .s-cats{display:grid;gap:22px}
  .s-cat{position:relative;overflow:hidden;border-radius:26px;background:var(--card);border:1px solid var(--line);padding:30px 30px 26px}
  .s-cat::before{content:'';position:absolute;left:0;top:0;right:0;height:8px;background:var(--c)}
  .s-cat-top{display:flex;align-items:flex-end;justify-content:space-between;gap:16px}
  .s-cat-dist{font-family:'Archivo',sans-serif;font-weight:900;font-size:110px;line-height:.85;letter-spacing:-.03em;color:var(--c)}
  .s-cat-tag{font-family:'Archivo',sans-serif;font-weight:800;font-size:20px;letter-spacing:.16em;text-transform:uppercase;color:var(--soft);padding-bottom:8px}
  .s-stats{display:grid;grid-template-columns:repeat(12,1fr);gap:14px;margin-top:26px}
  .s-stat.w3{grid-column:span 3}
  .s-stat.w3 b{font-size:28px;white-space:nowrap}
  .s-stat.w4{grid-column:span 4}
  .s-stat{background:rgba(255,255,255,.04);border:1px solid var(--line);border-radius:16px;padding:16px 18px}
  .s-stat small{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:14px;letter-spacing:.16em;text-transform:uppercase;color:var(--mute)}
  .s-stat b{display:block;font-family:'Archivo',sans-serif;font-weight:900;font-size:32px;margin-top:6px;line-height:1.05}

  /* Course */
  .s-course .s-title{font-size:96px}
  .s-course .s-fit{margin-top:26px}
  .s-cstats{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}
  .s-cstats .s-chip{font-size:20px;padding:11px 18px;background:#141414}
  .s-map{position:relative;border-radius:22px;background:#121212;border:1px solid var(--line);overflow:hidden}
  .s-map svg,.s-ele svg{display:block;width:100%;height:auto}
  /* Lebar kotak = lebar grafik, agar bingkai tetap pas walau slide diperkecil otomatis. */
  .s-map,.s-ele{max-width:938px;margin-left:auto;margin-right:auto}
  .s-ele{margin-top:16px;border-radius:18px;background:#121212;border:1px solid var(--line);padding:16px 0 10px;position:relative}
  .s-ele-lbl{display:flex;justify-content:space-between;padding:0 20px;font-family:'Archivo',sans-serif;font-weight:800;font-size:15px;letter-spacing:.12em;text-transform:uppercase;color:var(--mute);margin-bottom:6px}
  .s-pts{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px}
  .s-pt{display:inline-flex;align-items:center;gap:10px;padding:10px 16px;border-radius:12px;background:#141414;border:1px solid var(--line);font-size:19px;font-weight:600;color:var(--soft)}
  .s-pt b{font-family:'Archivo',sans-serif;font-weight:900;font-size:19px;color:#fff;padding:3px 9px;border-radius:7px;background:var(--pc)}

  /* Info jalur */
  .s-poi{display:flex;gap:20px;align-items:flex-start;padding:20px 24px;border-radius:18px;background:var(--card);border:1px solid var(--line)}
  .s-poi + .s-poi{margin-top:12px}
  .s-poi .ic{font-size:40px;line-height:1}
  .s-poi b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:28px}
  .s-poi small{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:16px;letter-spacing:.14em;text-transform:uppercase;color:var(--red);margin-top:4px}
  .s-poi p{font-size:23px;line-height:1.4;color:var(--soft);margin-top:6px}
  .s-poi.hz{border-color:rgba(229,57,53,.45);background:linear-gradient(90deg,rgba(229,57,53,.12),var(--card) 60%)}

  /* Aturan */
  .s-rules{display:flex;flex-direction:column;gap:26px}
  .s-rule{display:flex;gap:26px}
  .s-rule .no{font-family:'Archivo',sans-serif;font-weight:900;font-size:54px;line-height:.9;color:var(--red);min-width:84px;letter-spacing:-.02em}
  .s-rule b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:31px;line-height:1.2}
  .s-rule p{font-size:25px;line-height:1.45;color:var(--soft);margin-top:6px;white-space:pre-line}

  /* Awards */
  .s-award{border-radius:24px;background:var(--card);border:1px solid var(--line);padding:28px 30px;border-left:8px solid var(--c)}
  .s-award + .s-award{margin-top:20px}
  .s-award h3{display:flex;align-items:baseline;gap:16px;font-family:'Archivo',sans-serif;font-weight:900;font-size:56px;line-height:1;color:var(--c);margin-bottom:18px}
  .s-award h3 small{font-size:20px;letter-spacing:.16em;text-transform:uppercase;color:var(--soft);font-weight:800}
  .s-award li{display:flex;gap:16px;align-items:center;font-size:29px;line-height:1.35;padding:8px 0}
  .s-award li span{font-size:32px}

  /* QR */
  .s-qrs{display:grid;gap:26px}
  .s-qr{border-radius:26px;background:var(--card);border:1px solid var(--line);padding:28px;text-align:center}
  .s-qr .plate{background:#fff;border-radius:18px;padding:22px;display:inline-block}
  .s-qr .plate img{display:block;width:100%;height:auto}
  .s-qr h3{font-family:'Archivo',sans-serif;font-weight:900;font-size:58px;line-height:1;color:var(--c);margin-top:22px}
  .s-qr p{font-size:22px;color:var(--soft);margin-top:10px;line-height:1.4}
  .s-qr .gpx{display:inline-block;margin-top:14px;padding:8px 16px;border-radius:999px;background:rgba(229,57,53,.15);color:#fff;font-family:'Archivo',sans-serif;font-weight:800;font-size:17px;letter-spacing:.12em;text-transform:uppercase}

  /* Sponsor */
  .s-spons{--cols:2;--ph:200px;--nfs:22px;display:flex;flex-wrap:wrap;justify-content:center;gap:18px 16px}
  .s-spon{width:calc((100% - (var(--cols) - 1) * 16px) / var(--cols));max-width:330px;text-align:center}
  .s-spon .plate{height:var(--ph);border-radius:16px;background:#fff;display:flex;align-items:center;justify-content:center;padding:calc(var(--ph) * .1) calc(var(--ph) * .12)}
  .s-spon .plate img{max-width:100%;max-height:100%;object-fit:contain}
  .s-spon .plate.txt{background:#151515;border:1px solid var(--line);font-family:'Archivo',sans-serif;font-weight:900;font-size:calc(var(--nfs) * 1.15);line-height:1.15;color:#fff}
  .s-spon b{display:block;margin-top:10px;font-family:'Archivo',sans-serif;font-weight:800;font-size:var(--nfs);line-height:1.2}
  .s-sups{display:flex;flex-wrap:wrap;justify-content:center;gap:22px 20px;margin-top:6px}
  .s-sup{width:200px;text-align:center}
  .s-sups.big{gap:34px 18px}
  .s-sups.big .s-sup{width:220px}
  .s-sups.big .s-sup img{height:170px}
  .s-sups.big .s-sup b{font-size:20px}
  .s-sup img{height:140px;width:auto;max-width:100%;object-fit:contain;display:block;margin:0 auto}
  .s-sup b{display:block;margin-top:10px;font-size:17px;line-height:1.3;color:var(--soft);font-weight:600}
  /* RPC requirements */
  .s-reqs{display:flex;flex-direction:column;gap:14px}
  .s-req{display:flex;align-items:center;gap:22px;padding:22px 26px;border-radius:20px;background:var(--card);border:1px solid var(--line);font-size:29px;line-height:1.35;font-weight:600}
  .s-req .ck{flex-shrink:0;width:46px;height:46px;border-radius:50%;background:var(--red);display:grid;place-items:center}
  .s-req .ck svg{width:26px;height:26px}
  .s-alert{margin-top:26px;border-radius:22px;padding:26px 30px;border:1.5px solid rgba(229,57,53,.5);background:linear-gradient(135deg,rgba(229,57,53,.16),rgba(229,57,53,.03))}
  .s-alert .s-list{margin-top:16px;gap:12px}
  .s-alert .s-list li{font-size:26px}
  .s-contact{display:flex;align-items:center;gap:16px;margin-top:24px;font-size:26px;color:var(--soft)}
  .s-contact b{color:#fff;font-family:'Archivo',sans-serif;font-weight:800}
  .s-contact svg{width:34px;height:34px;color:var(--red);flex-shrink:0}

  /* Race village & parkir */
  .s-venue{display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start}
  .s-venue.noqr{grid-template-columns:1fr}
  .s-steps{list-style:none;counter-reset:st;display:flex;flex-direction:column;gap:16px}
  .s-steps li{counter-increment:st;position:relative;padding-left:66px;font-size:27px;line-height:1.42;color:#E6E6E6;min-height:46px}
  .s-steps li::before{content:counter(st);position:absolute;left:0;top:-2px;width:46px;height:46px;border-radius:12px;background:var(--red);display:grid;place-items:center;font-family:'Archivo',sans-serif;font-weight:900;font-size:24px;color:#fff}
  .s-mapqr{border-radius:22px;background:var(--card);border:1px solid var(--line);padding:20px;text-align:center}
  .s-mapqr .plate{background:#fff;border-radius:14px;padding:14px}
  .s-mapqr .plate img{display:block;width:100%;height:auto}
  .s-mapqr p{font-family:'Archivo',sans-serif;font-weight:800;font-size:17px;letter-spacing:.12em;text-transform:uppercase;margin-top:14px;color:var(--soft)}
  .s-facs{display:flex;flex-wrap:wrap;gap:12px}
  .s-fac{display:inline-flex;align-items:center;gap:10px;padding:14px 20px;border-radius:14px;background:var(--card);border:1px solid var(--line);font-family:'Archivo',sans-serif;font-weight:700;font-size:23px}
  .s-fac::before{content:'';width:10px;height:10px;border-radius:50%;background:var(--red)}
  .s-place{display:flex;align-items:center;gap:22px;margin-bottom:28px}
  .s-place .ic{display:grid;place-items:center;width:68px;height:68px;border-radius:18px;background:var(--red);flex-shrink:0}
  .s-place .ic svg{width:36px;height:36px;color:#fff}
  .s-place .s-val{margin-top:0}
  .s-place p{font-size:24px;line-height:1.4;color:var(--soft);margin-top:8px}

  /* Detail titik pos (timeline) */
  .s-stops{position:relative;display:flex;flex-direction:column;gap:16px}
  .s-stop{position:relative;display:grid;grid-template-columns:150px 1fr;gap:22px;align-items:stretch}
  .s-stop:not(:last-child) .s-badge::after{content:'';position:absolute;left:50%;top:100%;width:3px;height:16px;background:rgba(255,255,255,.18);transform:translateX(-50%)}
  .s-badge{position:relative;border-radius:18px;background:var(--pc);color:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px 8px;text-align:center}
  .s-badge b{font-family:'Archivo',sans-serif;font-weight:900;font-size:30px;line-height:1;letter-spacing:-.01em}
  .s-badge small{font-family:'Archivo',sans-serif;font-weight:800;font-size:17px;margin-top:8px;opacity:.9}
  .s-badge.lt{color:#0A0A0A}
  .s-stop-body{border-radius:18px;background:var(--card);border:1px solid var(--line);padding:18px 22px}
  .s-stop-top{display:flex;flex-wrap:wrap;align-items:center;gap:10px 14px}
  .s-stop-top h4{font-family:'Archivo',sans-serif;font-weight:800;font-size:29px;line-height:1.15;margin-right:auto}
  .s-cot{padding:6px 12px;border-radius:9px;background:rgba(229,57,53,.18);border:1px solid rgba(229,57,53,.5);font-family:'Archivo',sans-serif;font-weight:800;font-size:18px;letter-spacing:.06em}
  .s-time{padding:6px 12px;border-radius:9px;background:rgba(255,255,255,.08);font-family:'Archivo',sans-serif;font-weight:800;font-size:18px;letter-spacing:.06em}
  .s-stop-loc{font-size:22px;color:var(--soft);margin-top:8px;line-height:1.35}
  .s-stop-fac{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
  .s-stop-fac span{padding:5px 12px;border-radius:8px;background:rgba(255,255,255,.06);border:1px solid var(--line);font-size:18px;font-weight:600;color:#E6E6E6}
  .s-stop-note{font-size:21px;line-height:1.4;color:var(--soft);margin-top:10px;font-style:italic}
  .s-legend{display:flex;flex-wrap:wrap;gap:18px;margin-top:20px;font-family:'Archivo',sans-serif;font-weight:700;font-size:18px;color:var(--soft)}
  .s-legend span{display:inline-flex;align-items:center;gap:8px}
  .s-legend i{width:14px;height:14px;border-radius:4px;background:var(--pc)}

  .s-center{text-align:center}
  /* Tengah secara vertikal tapi "aman": bila isi lebih tinggi dari area, mulai dari atas (judul tidak terpotong). */
  .s-center .s-fit{display:flex;flex-direction:column}
  .s-center .s-fit-in{margin:auto 0}
  .s-center .s-eye{justify-content:center}
</style>
@endpush

@section('content')
<div class="rg-wrap">
  <div class="rg-bar">
    <h1><small>GTR Admin</small>Race Guide</h1>
    <label><input type="checkbox" id="rg-edit"> Edit teks</label>
    <label>Resolusi
      <select id="rg-scale">
        <option value="1">1080 × 1350 (Instagram)</option>
        <option value="2">2160 × 2700 (2×, cetak)</option>
      </select>
    </label>
    <button type="button" class="rg-btn" id="rg-all">Unduh semua (ZIP)</button>
    <button type="button" class="rg-btn" id="rg-pdf">Unduh semua (PDF)</button>
    <div class="rg-status" id="rg-status"></div>
    <div class="rg-pub">
      @if($pub->raceGuidePublished())
        <span class="rg-pub-st on">● Tayang di website</span>
        <span>Dipublikasikan {{ $pub->race_guide_published_at->timezone('Asia/Makassar')->translatedFormat('d M Y, H:i') }} WITA · {{ count($pub->race_guide_slides) }} slide</span>
        <a href="{{ route('gtr.guide') }}" target="_blank" rel="noopener">Lihat halaman →</a>
      @else
        <span class="rg-pub-st">○ Belum dipublikasikan</span>
        <span>Kalau sudah final, publikasikan agar peserta bisa melihat &amp; mengunduh di menu Race Guide website.</span>
      @endif
      <span style="margin-left:auto;display:flex;gap:8px">
        @if($pub->raceGuidePublished())<button type="button" class="rg-btn ghost" id="rg-unpub">Tarik dari website</button>@endif
        <button type="button" class="rg-btn" id="rg-pub">{{ $pub->raceGuidePublished() ? 'Perbarui di website' : 'Publikasikan ke website' }}</button>
      </span>
    </div>
  </div>

  @if($g['missing'])
    <div class="rg-missing">
      <b>Beberapa slide dilewati / belum lengkap karena kontennya belum diisi di admin:</b>
      <ul>@foreach($g['missing'] as $m)<li>{{ $m }}</li>@endforeach</ul>
    </div>
  @endif

  <div class="rg-grid" id="rg-grid">

    {{-- 1. COVER --}}
    <div class="rg-item" data-name="cover">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked title="Sertakan di ZIP"><span class="nm">Cover</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <img class="s-cover-img" src="{{ $g['event']['cover'] }}" alt="">
        <div class="s-cover-shade"></div>
        @include('admin.race-guide.head')
        <div class="s-cover">
          <div class="s-eye" data-edit>Official Race Guide</div>
          <h1 class="s-title" data-edit>Your Race<br>Guide <em>Is Here</em></h1>
          <p class="s-lead" data-edit>Baca dengan teliti dan pastikan kamu benar-benar siap sebelum turun ke trail.</p>
          <div class="s-chips">
            @if($g['event']['date'])
              <span class="s-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg><span data-edit>{{ $g['event']['date'] }}</span></span>
            @endif
            @if($g['event']['categories'])
              <span class="s-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 21V4M5 4h11l-2 4 2 4H5"/></svg><span data-edit>{{ $g['event']['categories'] }}</span></span>
            @endif
            @if($g['event']['location'])
              <span class="s-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg><span data-edit>{{ $g['event']['location'] }}</span></span>
            @endif
          </div>
          <div class="s-swipe" data-edit>Swipe for details <i>→</i></div>
        </div>
        <div class="s-foot" style="height:auto"><div class="s-foot-row" style="bottom:34px;justify-content:flex-end"><span class="s-pg"><b class="pg-n">01</b> / <span class="pg-t">01</span></span></div></div>
      </div></div>
    </div>

    {{-- 1b. EVENT TIMELINE: rundown semua kategori digabung jadi satu --}}
    @if($g['timeline'])
    @php $catById = $cats->keyBy('id'); @endphp
    <div class="rg-item" data-name="event-timeline">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Event Timeline</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Event Rundown</div>
          <h1 class="s-title" data-edit>Event <em>Timeline</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-tl">
              @foreach($g['timeline'] as $di => $d)
                <div class="s-tl-day {{ $d['race'] ? 'race' : '' }}">
                  <div class="s-tl-date">
                    <span class="n">Day {{ $di + 1 }}</span>
                    <b data-edit>{{ $d['date'] ?: '-' }}</b>
                    @if($d['race'])<span class="rd">Race Day</span>@endif
                  </div>
                  <div class="s-tl-items">
                    @foreach($d['items'] as $it)
                      <div class="s-tl-it">
                        <div class="t" data-edit>{{ $it['time'] ?: '-' }}</div>
                        <div class="d">
                          <b data-edit>{{ $it['activity'] ?: '-' }}</b>
                          @if($it['location'])<span data-edit>{{ $it['location'] }}</span>@endif
                          @if($it['cats'])
                            <div class="s-tl-cats">@foreach($it['cats'] as $cid)@if($catById->has($cid))<i style="--c:{{ $catById[$cid]['color'] }}">{{ $catById[$cid]['distance'] }}</i>@endif @endforeach</div>
                          @endif
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endforeach
            </div>
            <div class="s-tl-note" data-edit>All times in WITA (UTC+8)</div>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 2. RACE PACK COLLECTION --}}
    @if($hasRacepack)
    <div class="rg-item" data-name="race-pack">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Race Pack</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Before Race Day</div>
          <h1 class="s-title" data-edit>Race Pack<br><em>Collection</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            {{-- Jadwal + lokasi RPC (nama, alamat, petunjuk arah) di kiri, QR Google Maps di kanan — seperti Race Village. --}}
            <div class="s-venue {{ $g['racepack']['qr'] ? '' : 'noqr' }}">
              <div>
                @if($g['racepack']['schedule'])
                  <div class="s-card" style="margin-bottom:20px"><div class="s-lbl">Jadwal</div><div class="s-val" data-edit>{{ $g['racepack']['schedule'] }}</div></div>
                @endif
                @if($g['racepack']['location'] || $g['racepack']['address'])
                  <div class="s-card s-place">
                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span>
                    <div>
                      @if($g['racepack']['location'])<div class="s-val" data-edit>{{ $g['racepack']['location'] }}</div>@endif
                      @if($g['racepack']['address'])<p data-edit>{{ $g['racepack']['address'] }}</p>@endif
                    </div>
                  </div>
                @endif
                @if($g['racepack']['directions'])
                  <div class="s-h3" style="margin-top:0" data-edit>Petunjuk menuju lokasi RPC</div>
                  <ol class="s-steps">@foreach($g['racepack']['directions'] as $d)<li data-edit>{{ $d }}</li>@endforeach</ol>
                @endif
              </div>
              @if($g['racepack']['qr'])
                <div class="s-mapqr"><div class="plate"><img src="{{ $g['racepack']['qr'] }}" alt="QR Maps RPC"></div><p data-edit>Scan · Google Maps</p></div>
              @endif
            </div>
            @if($g['racepack']['notes'])
              <div class="s-h3" data-edit>Yang perlu diperhatikan</div>
              <ul class="s-list">@foreach($g['racepack']['notes'] as $n)<li data-edit>{{ $n }}</li>@endforeach</ul>
            @endif
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 2b. RPC REQUIREMENTS --}}
    @if($hasRpc)
    <div class="rg-item" data-name="rpc-requirements">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">RPC Requirements</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Race Pack Collection</div>
          <h1 class="s-title" data-edit>RPC <em>Requirements</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            @if($g['rpc']['requirements'])
              <div class="s-reqs">
                @foreach($g['rpc']['requirements'] as $r)
                  <div class="s-req"><span class="ck"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span><span data-edit>{{ $r }}</span></div>
                @endforeach
              </div>
            @endif
            @if($g['rpc']['proxy'])
              <div class="s-alert">
                <div class="s-lbl" style="color:#fff" data-edit>Tidak bisa hadir saat RPC?</div>
                <ul class="s-list">@foreach($g['rpc']['proxy'] as $n)<li data-edit>{{ $n }}</li>@endforeach</ul>
              </div>
            @endif
            @if($g['rpc']['contact'])
              <div class="s-contact"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg><span data-edit>Info lebih lanjut: <b>{{ $g['rpc']['contact'] }}</b></span></div>
            @endif
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 2c. RACE VILLAGE --}}
    @if($hasVenue)
    <div class="rg-item" data-name="race-village">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Race Village</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Venue &amp; Facilities</div>
          <h1 class="s-title" data-edit>Race <em>Village</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            @if($g['venue']['name'] || $g['venue']['address'])
              <div class="s-card s-place">
                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span>
                <div>
                  @if($g['venue']['name'])<div class="s-val" data-edit>{{ $g['venue']['name'] }}</div>@endif
                  @if($g['venue']['address'])<p data-edit>{{ $g['venue']['address'] }}</p>@endif
                </div>
              </div>
            @endif
            <div class="s-venue {{ $g['venue']['qr'] ? '' : 'noqr' }}">
              <div>
                @if($g['venue']['directions'])
                  <div class="s-h3" style="margin-top:0" data-edit>Petunjuk menuju venue</div>
                  <ol class="s-steps">@foreach($g['venue']['directions'] as $d)<li data-edit>{{ $d }}</li>@endforeach</ol>
                @endif
              </div>
              @if($g['venue']['qr'])
                <div class="s-mapqr"><div class="plate"><img src="{{ $g['venue']['qr'] }}" alt="QR Maps"></div><p data-edit>Scan · Google Maps</p></div>
              @endif
            </div>
            @if($g['venue']['facilities'])
              <div class="s-h3" data-edit>Fasilitas race village</div>
              <div class="s-facs">@foreach($g['venue']['facilities'] as $f)<span class="s-fac" data-edit>{{ $f }}</span>@endforeach</div>
            @endif
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 2d. PARKING --}}
    @if($hasParking)
    <div class="rg-item" data-name="parking">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Parking</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Parking Location</div>
          <h1 class="s-title" data-edit>Parking <em>Area</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            @if($g['parking']['name'])
              <div class="s-card s-place" style="margin-bottom:30px">
                <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/></svg></span>
                <div><div class="s-val" data-edit>{{ $g['parking']['name'] }}</div></div>
              </div>
            @endif
            <p class="s-lead" style="margin-bottom:30px" data-edit>Demi keamanan dan kelancaran acara, mohon ikuti petunjuk parkir berikut:</p>
            @if($g['parking']['notes'])
              <ol class="s-steps">@foreach($g['parking']['notes'] as $n)<li data-edit>{{ $n }}</li>@endforeach</ol>
            @endif
            <p class="s-lead" style="margin-top:30px" data-edit>Terima kasih atas kerja samanya menjaga race day tetap aman, tertib, dan nyaman untuk semua.</p>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 3. RACE DAY --}}
    @if($cats->isNotEmpty())
    <div class="rg-item" data-name="race-day">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Race Day</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Race Day Schedule</div>
          <h1 class="s-title" data-edit>Race <em>Day</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-pair {{ $g['assembly'] ? '' : 'one' }}">
              <div class="s-card"><div class="s-lbl">Tanggal</div><div class="s-val" data-edit>{{ $g['event']['date'] ?: '-' }}</div></div>
              @if($g['assembly'])<div class="s-card"><div class="s-lbl">Jam Kumpul</div><div class="s-val" data-edit>{{ $g['assembly'] }}</div></div>@endif
            </div>
            @if($g['event']['location'])
              <div class="s-card" style="margin-top:20px"><div class="s-lbl">Start &amp; Finish</div><div class="s-val" style="font-size:32px" data-edit>{{ $g['event']['location'] }}</div></div>
            @endif
            <table class="s-table" style="margin-top:28px">
              <tr><th>Kategori</th><th>Start</th><th>Cut Off</th></tr>
              @foreach($cats as $c)
                <tr>
                  <td><div class="cat"><span class="s-dot" style="background:{{ $c['color'] }}"></span><span><span data-edit>{{ $c['distance'] }}</span>@if($c['tag'])<small data-edit>{{ $c['tag'] }}</small>@endif</span></div></td>
                  <td class="big" data-edit>{{ $c['start'] ?: '-' }}</td>
                  <td class="big" data-edit>{{ $c['cot'] ?: '-' }}</td>
                </tr>
              @endforeach
            </table>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>

    {{-- 4. KATEGORI --}}
    <div class="rg-item" data-name="categories">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Race Categories</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Choose Your Trail</div>
          <h1 class="s-title" data-edit>Race <em>Categories</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-cats">
              @foreach($cats as $c)
                <div class="s-cat" style="--c:{{ $c['color'] }}">
                  <div class="s-cat-top">
                    <div class="s-cat-dist" data-edit>{{ $c['distance'] }}</div>
                    @if($c['tag'])<div class="s-cat-tag" data-edit>{{ $c['tag'] }}</div>@endif
                  </div>
                  <div class="s-stats">
                    <div class="s-stat w3"><small>Jarak</small><b data-edit>{{ $c['km_label'] ? rtrim(rtrim(number_format($c['km_label'], 2, ',', '.'), '0'), ',') . ' KM' : '-' }}</b></div>
                    <div class="s-stat w3"><small>Elevasi</small><b data-edit>{{ $c['gain'] ?: '-' }}</b></div>
                    <div class="s-stat w3"><small>Start</small><b data-edit>{{ $c['start'] ?: '-' }}</b></div>
                    <div class="s-stat w3"><small>Cut Off</small><b data-edit>{{ $c['cot'] ?: '-' }}</b></div>
                    <div class="s-stat w4"><small>Water Station</small><b data-edit>{{ $c['ws'] ? $c['ws'] . ' Pos' : '-' }}</b></div>
                    <div class="s-stat w4"><small>Check Point</small><b data-edit>{{ $c['cp'] ? $c['cp'] . ' Pos' : '-' }}</b></div>
                    <div class="s-stat w4"><small>Cut Off Point</small><b data-edit>{{ $c['cop'] ? $c['cop'] . ' Pos' : '-' }}</b></div>
                  </div>
                </div>
              @endforeach
            </div>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 5. COURSE per kategori --}}
    @foreach($cats as $c)
    @if($c['map'] || $c['elevation'])
    <div class="rg-item" data-name="course-{{ \Illuminate\Support\Str::slug($c['name']) }}">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Course {{ $c['distance'] }}</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide s-course">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Course Map{{ $c['tag'] ? ' · ' . $c['tag'] : '' }}</div>
          <h1 class="s-title" data-edit>Course <em style="color:{{ $c['color'] }}">{{ $c['distance'] }}</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-cstats">
              @if($c['km'])<span class="s-chip" data-edit>{{ rtrim(rtrim(number_format($c['km'], 2, ',', '.'), '0'), ',') }} KM</span>@endif
              @if($c['gain'])<span class="s-chip" data-edit>Elevasi {{ $c['gain'] }}</span>@endif
              @if($c['start'])<span class="s-chip" data-edit>Start {{ $c['start'] }}</span>@endif
              @if($c['cot'])<span class="s-chip" data-edit>COT {{ $c['cot'] }}</span>@endif
              @if($c['cop'])<span class="s-chip" data-edit>COP {{ $c['cop'] }} Pos</span>@endif
            </div>
            @if($m = $c['map'])
              <div class="s-map">
                <svg width="{{ $m['w'] }}" height="{{ $m['h'] }}" viewBox="0 0 {{ $m['w'] }} {{ $m['h'] }}">
                  <path d="{{ $m['path'] }}" fill="none" stroke="{{ $c['color'] }}" stroke-opacity=".22" stroke-width="18" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="{{ $m['path'] }}" fill="none" stroke="{{ $c['color'] }}" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                  @foreach($m['points'] as $p)
                    @php $pc = $pointColor[$p['type']] ?? '#fff'; [$lx, $ly, $lw] = $p['label']; @endphp
                    <g transform="translate({{ $p['xy'][0] }} {{ $p['xy'][1] }})">
                      <line x1="0" y1="0" x2="{{ $lx + $lw / 2 }}" y2="{{ $ly + 15 }}" stroke="{{ $pc }}" stroke-width="2"/>
                      <circle r="11" fill="{{ $pc }}" stroke="#fff" stroke-width="4"/>
                      <rect x="{{ $lx }}" y="{{ $ly }}" width="{{ $lw }}" height="30" rx="8" fill="{{ $pc }}"/>
                      <text x="{{ $lx + $lw / 2 }}" y="{{ $ly + 21 }}" text-anchor="middle" font-family="Archivo, sans-serif" font-weight="900" font-size="18" fill="#fff">{{ $p['code'] }}</text>
                    </g>
                  @endforeach
                  @if($m['loop'])
                    <g transform="translate({{ $m['start'][0] }} {{ $m['start'][1] }})">
                      <circle r="16" fill="#fff" stroke="#0A0A0A" stroke-width="4"/>
                      <rect x="-118" y="22" width="236" height="34" rx="9" fill="#fff"/>
                      <text y="46" text-anchor="middle" font-family="Archivo, sans-serif" font-weight="900" font-size="18" fill="#0A0A0A">START / FINISH</text>
                    </g>
                  @else
                    @foreach([['start', 'START', '#22C55E'], ['finish', 'FINISH', '#fff']] as [$k, $lbl, $fc])
                      <g transform="translate({{ $m[$k][0] }} {{ $m[$k][1] }})">
                        <circle r="15" fill="{{ $fc }}" stroke="#0A0A0A" stroke-width="4"/>
                        <rect x="-56" y="22" width="112" height="32" rx="9" fill="{{ $fc }}"/>
                        <text y="44" text-anchor="middle" font-family="Archivo, sans-serif" font-weight="900" font-size="17" fill="#0A0A0A">{{ $lbl }}</text>
                      </g>
                    @endforeach
                  @endif
                </svg>
              </div>
            @endif
            @if($e = $c['elevation'])
              <div class="s-ele">
                <div class="s-ele-lbl"><span>Profil Elevasi</span><span>{{ $e['min'] }}–{{ $e['max'] }} m · {{ str_replace('.', ',', $e['km']) }} km</span></div>
                <svg width="{{ $e['w'] }}" height="{{ $e['h'] }}" viewBox="0 0 {{ $e['w'] }} {{ $e['h'] }}">
                  <defs><linearGradient id="eg-{{ $c['id'] }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{{ $c['color'] }}" stop-opacity=".55"/><stop offset="1" stop-color="{{ $c['color'] }}" stop-opacity=".04"/></linearGradient></defs>
                  <path d="{{ $e['area'] }}" fill="url(#eg-{{ $c['id'] }})"/>
                  <path d="{{ $e['line'] }}" fill="none" stroke="{{ $c['color'] }}" stroke-width="3.5" stroke-linejoin="round"/>
                  @foreach($e['points'] as $p)
                    @php $pc = $pointColor[$p['type']] ?? '#fff'; @endphp
                    <line x1="{{ $p['x'] }}" x2="{{ $p['x'] }}" y1="{{ $p['ly'] + $p['lh'] }}" y2="{{ $e['h'] }}" stroke="{{ $pc }}" stroke-width="2" stroke-dasharray="5 5"/>
                    <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="7" fill="{{ $pc }}" stroke="#fff" stroke-width="3"/>
                    <rect x="{{ $p['lx'] }}" y="{{ $p['ly'] }}" width="{{ $p['lw'] }}" height="{{ $p['lh'] }}" rx="7" fill="{{ $pc }}"/>
                    <text x="{{ $p['lx'] + $p['lw'] / 2 }}" y="{{ $p['ly'] + 19 }}" text-anchor="middle" font-family="Archivo, sans-serif" font-weight="900" font-size="16" fill="#fff">{{ $p['code'] }}</text>
                  @endforeach
                </svg>
              </div>
            @endif
            @if($c['points'])
              <div class="s-pts">
                @foreach($c['points'] as $p)
                  <span class="s-pt" style="--pc:{{ $pointColor[$p['type']] ?? '#555' }}"><b>{{ $p['code'] }}</b><span data-edit>KM {{ str_replace('.', ',', $p['km']) }}{{ $p['cutoff'] ? ' · COT ' . $p['cutoff'] : '' }}</span></span>
                @endforeach
              </div>
            @endif
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 5b. DETAIL TITIK POS (setelah peta kategori yang sama) --}}
    @foreach(collect($stopSlides)->where('cat.id', $c['id']) as $st)
    <div class="rg-item" data-name="course-points-{{ \Illuminate\Support\Str::slug($c['name']) }}{{ $st['parts'] > 1 ? '-' . $st['part'] : '' }}">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Course Points {{ $c['distance'] }}{{ $st['parts'] > 1 ? ' (' . $st['part'] . '/' . $st['parts'] . ')' : '' }}</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide s-course">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Water Stations &amp; Checkpoints{{ $st['parts'] > 1 ? ' · ' . $st['part'] . '/' . $st['parts'] : '' }}</div>
          <h1 class="s-title" data-edit>Course Points<br><em style="color:{{ $c['color'] }}">{{ $c['distance'] }}</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-stops">
              @foreach($st['rows'] as $r)
                @php $pc = $stopColor[$r['type']] ?? '#555'; @endphp
                <div class="s-stop" style="--pc:{{ $pc }}">
                  <div class="s-badge {{ $r['type'] === 'finish' ? 'lt' : '' }}"><b>{{ $r['code'] }}</b>@if($r['km'])<small>{{ $r['km'] }}</small>@endif</div>
                  <div class="s-stop-body">
                    <div class="s-stop-top">
                      <h4 data-edit>{{ $r['title'] }}</h4>
                      @if($r['time'])<span class="s-time" data-edit>{{ $r['time'] }}</span>@endif
                      @if($r['cutoff'])<span class="s-cot" data-edit>COT {{ $r['cutoff'] }} WITA</span>@endif
                    </div>
                    @if($r['location'])<div class="s-stop-loc" data-edit>📍 {{ $r['location'] }}</div>@endif
                    @if($r['facilities'])<div class="s-stop-fac">@foreach($r['facilities'] as $f)<span data-edit>{{ $f }}</span>@endforeach</div>@endif
                    @if($r['notes'])<div class="s-stop-note" data-edit>{{ $r['notes'] }}</div>@endif
                  </div>
                </div>
              @endforeach
            </div>
            <div class="s-legend">
              <span style="--pc:#3B82F6"><i></i>Water Station</span>
              <span style="--pc:#F59E0B"><i></i>Check Point</span>
              <span style="--pc:#E53935"><i></i>Cut Off Point</span>
            </div>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endforeach
    @endforeach

    {{-- 6. MANDATORY GEAR --}}
    @if($g['gear'])
    <div class="rg-item" data-name="mandatory-gear">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Mandatory Gear</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Required Equipment</div>
          <h1 class="s-title" data-edit>Mandatory <em>Gear</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <table class="s-table">
              <tr><th data-edit>Perlengkapan</th>@foreach($cats as $c)<th style="width:170px" data-edit>{{ $c['distance'] }}</th>@endforeach</tr>
              @foreach($g['gear'] as $row)
                <tr>
                  <td data-edit>{{ $row['item'] }}</td>
                  @foreach($cats as $c)
                    <td>
                      @if(in_array($c['id'], $row['cats']))
                        <svg class="s-check" viewBox="0 0 24 24"><circle cx="12" cy="12" r="11" fill="{{ $c['color'] }}" fill-opacity=".18"/><path d="M6.5 12.5l3.5 3.5 7.5-8" fill="none" stroke="{{ $c['color'] }}" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                      @else
                        <span class="s-no">—</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
              @endforeach
            </table>
            {{-- Catatan pengganti soft flask (tampil bila soft flask ada di daftar gear). --}}
            @if(collect($g['gear'])->contains(fn ($r) => preg_match('/soft\s*-?\s*f?lask/i', $r['item'])))
              <div class="s-alert">
                <div class="s-lbl" style="color:#fff" data-edit>Catatan Soft Flask</div>
                <ul class="s-list">
                  <li data-edit>Soft flask boleh diganti dengan <b>botol air minum</b>.</li>
                  <li data-edit>Jaga kebersihan trail: jangan membuang botol atau sampah apa pun di sepanjang jalur.</li>
                  <li data-edit>Botol wajib dibawa sampai garis finish, lalu dibuang di tempat sampah yang tersedia di Race Village.</li>
                </ul>
              </div>
            @endif
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 7. COURSE INFO (bahaya & info jalur) --}}
    @foreach($infoCats as $c)
    <div class="rg-item" data-name="course-info-{{ \Illuminate\Support\Str::slug($c['name']) }}">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Course Highlights {{ $c['distance'] }}</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Course Info · {{ $c['distance'] }}</div>
          <h1 class="s-title" data-edit>Course <em>Highlights</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            @foreach($c['pois'] as $p)
              <div class="s-poi {{ $p['hazard'] ? 'hz' : '' }}">
                <span class="ic">{{ $p['icon'] }}</span>
                <div>
                  <b data-edit>{{ $p['title'] }}</b>
                  <small data-edit>KM {{ str_replace('.', ',', $p['km']) }}{{ $p['km_end'] ? '–' . str_replace('.', ',', $p['km_end']) : '' }} · {{ $p['label'] }}</small>
                  @if($p['note'])<p data-edit>{{ $p['note'] }}</p>@endif
                </div>
              </div>
            @endforeach
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endforeach

    {{-- 8. RACE RULES --}}
    @foreach($ruleChunks as $ci => $chunk)
    <div class="rg-item" data-name="race-rules-{{ $ci + 1 }}">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Race Rules{{ count($ruleChunks) > 1 ? ' ' . ($ci + 1) : '' }}</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Rules &amp; Regulations{{ count($ruleChunks) > 1 ? ' · ' . ($ci + 1) . '/' . count($ruleChunks) : '' }}</div>
          <h1 class="s-title" data-edit>Race <em>Rules</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-rules">
              @foreach($chunk as $r)
                <div class="s-rule">
                  <div class="no">{{ str_pad($r['n'], 2, '0', STR_PAD_LEFT) }}</div>
                  <div>@if($r['title'])<b data-edit>{{ $r['title'] }}</b>@endif @if($r['content'])<p data-edit>{{ $r['content'] }}</p>@endif</div>
                </div>
              @endforeach
            </div>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endforeach

    {{-- 9. AWARDS --}}
    @if($awardCats->isNotEmpty())
    <div class="rg-item" data-name="awards">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Awards</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Finisher &amp; Podium</div>
          <h1 class="s-title" data-edit>Race <em>Awards</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            @foreach($awardCats as $c)
              <div class="s-award" style="--c:{{ $c['color'] }}">
                <h3><span data-edit>{{ $c['distance'] }}</span>@if($c['tag'])<small data-edit>{{ $c['tag'] }}</small>@endif</h3>
                <ul style="list-style:none">
                  @foreach($c['awards'] as $a)
                    @php $ic = preg_match('/podium|juara|winner/i', $a) ? '🏆' : (preg_match('/medal|medali/i', $a) ? '🏅' : (preg_match('/sertifikat|certificate/i', $a) ? '📜' : '⭐')); @endphp
                    <li><span>{{ $ic }}</span><span data-edit style="font-size:29px">{{ $a }}</span></li>
                  @endforeach
                </ul>
              </div>
            @endforeach
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 10. QR COURSE / GPX --}}
    @if($cats->isNotEmpty())
    <div class="rg-item" data-name="course-qr">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Scan Here</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide s-center">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Course Map &amp; GPX</div>
          <h1 class="s-title" data-edit>Scan <em>Here</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            @php $qrW = $cats->count() === 1 ? 460 : ($cats->count() === 2 ? 330 : 230); @endphp
            <div class="s-qrs" style="grid-template-columns:repeat({{ min($cats->count(), 3) }},1fr)">
              @foreach($cats as $c)
                <div class="s-qr" style="--c:{{ $c['color'] }}">
                  <div class="plate" style="width:{{ $qrW + 44 }}px"><img src="{{ $c['qr'] }}" alt="QR {{ $c['distance'] }}"></div>
                  <h3 data-edit>{{ $c['distance'] }}</h3>
                  <p data-edit>Scan untuk detail rute, profil elevasi &amp; pos{{ $c['gpx'] ? ' — file GPX bisa diunduh di halaman ini' : '' }}.</p>
                  @if($c['gpx'])<span class="gpx" data-edit>GPX tersedia</span>@endif
                </div>
              @endforeach
            </div>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    {{-- 11. SPONSOR & SUPPORTED BY --}}
    @php
      // Ukuran logo menyesuaikan jumlah sponsor agar tidak perlu diperkecil paksa.
      // Lebih dari 12 sponsor: "Didukung oleh" dipindah ke slide sendiri.
      $spN = count($g['sponsors']);
      $spSplit = $spN > 12;
      [$spCols, $spPh, $spFs] = $spSplit
          ? ($spN <= 15 ? [5, 120, 16] : ($spN <= 20 ? [5, 96, 15] : [6, 84, 14]))
          : match (true) {
              $spN <= 2 => [2, 200, 22],
              $spN === 3 => [3, 170, 20],
              $spN === 4 => [4, 130, 18],
              $spN <= 6 => [3, 130, 18],
              $spN <= 8 => [4, 112, 17],
              default => [4, 92, 16],
          };
      $spStyle = "--cols:$spCols;--ph:{$spPh}px;--nfs:{$spFs}px";
    @endphp
    @if($g['sponsors'])
    <div class="rg-item" data-name="sponsors">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Sponsors</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide s-center">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Thank You</div>
          @if($spSplit)
            <h1 class="s-title" data-edit>Official <em>Sponsors</em></h1>
          @else
            <h1 class="s-title" data-edit>Supported <em>By</em></h1>
          @endif
          <div class="s-fit"><div class="s-fit-in">
            @unless($spSplit)<div class="s-h3" style="margin-top:0" data-edit>Official Sponsors</div>@endunless
            <div class="s-spons" style="{{ $spStyle }}">
              @foreach($g['sponsors'] as $sp)
                <div class="s-spon">
                  @if($sp['logo'])<div class="plate"><img src="{{ $sp['logo'] }}" alt="{{ $sp['name'] }}"></div>
                  @else<div class="plate txt" data-edit>{{ $sp['name'] }}</div>@endif
                  <b data-edit>{{ $sp['name'] }}</b>
                </div>
              @endforeach
            </div>
            @unless($spSplit)
              <div class="s-h3" data-edit>Supporting Partners</div>
              <div class="s-sups">
                @foreach($g['supporters'] as $sp)
                  <div class="s-sup"><img src="{{ $sp['logo'] }}" alt="{{ $sp['name'] }}"><b data-edit>{{ $sp['name'] }}</b></div>
                @endforeach
              </div>
            @endunless
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

    @if(! $g['sponsors'] || $spSplit)
    <div class="rg-item" data-name="supported-by">
      <div class="rg-item-bar"><input type="checkbox" class="rg-inc" checked><span class="nm">Supported By</span><button type="button" class="rg-one">Unduh PNG</button></div>
      <div class="rg-frame"><div class="slide s-center">
        <div class="s-topo" style="background-image:url('{{ $topoUri }}')"></div>
        @include('admin.race-guide.head')
        <div class="s-main">
          <div class="s-eye" data-edit>Supporting Partners</div>
          <h1 class="s-title" data-edit>Supported <em>By</em></h1>
          <div class="s-fit"><div class="s-fit-in">
            <div class="s-sups big">
              @foreach($g['supporters'] as $sp)
                <div class="s-sup"><img src="{{ $sp['logo'] }}" alt="{{ $sp['name'] }}"><b data-edit>{{ $sp['name'] }}</b></div>
              @endforeach
            </div>
          </div></div>
        </div>
        @include('admin.race-guide.foot')
      </div></div>
    </div>
    @endif

  </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
  const W = 1080, H = 1350;
  const items = [...document.querySelectorAll('.rg-item')];
  const status = document.getElementById('rg-status');
  const say = (t) => { status.textContent = t || ''; };

  // Skala pratinjau slide mengikuti lebar kartu.
  const ro = new ResizeObserver(entries => entries.forEach(e => {
    e.target.querySelector('.slide').style.transform = 'scale(' + (e.contentRect.width / W) + ')';
  }));
  items.forEach(it => ro.observe(it.querySelector('.rg-frame')));

  // Konten yang terlalu panjang diperkecil agar muat di area slide.
  function fit(slide) {
    const box = slide.querySelector('.s-fit');
    const inner = box && box.querySelector('.s-fit-in');
    if (!inner) return;
    inner.style.transform = ''; inner.style.width = '';
    const k = Math.min(1, box.clientHeight / inner.scrollHeight);
    if (k < 1) {
      inner.style.transform = 'scale(' + k + ')';
      inner.style.width = (100 / k) + '%';
    }
  }
  const fitAll = () => items.forEach(it => fit(it.querySelector('.slide')));

  // Nomor halaman hanya menghitung slide yang disertakan.
  function renumber() {
    const on = items.filter(it => it.querySelector('.rg-inc').checked);
    items.forEach(it => it.classList.toggle('off', !on.includes(it)));
    on.forEach((it, i) => {
      it.querySelectorAll('.pg-n').forEach(el => el.textContent = String(i + 1).padStart(2, '0'));
      it.querySelectorAll('.pg-t').forEach(el => el.textContent = String(on.length).padStart(2, '0'));
    });
  }
  items.forEach(it => it.querySelector('.rg-inc').addEventListener('change', renumber));
  renumber();

  // Mode edit teks: semua elemen bertanda data-edit bisa diketik langsung.
  document.getElementById('rg-edit').addEventListener('change', e => {
    document.body.classList.toggle('editing', e.target.checked);
    document.querySelectorAll('.slide [data-edit]').forEach(el => {
      if (e.target.checked) { el.setAttribute('contenteditable', 'true'); el.spellcheck = false; }
      else el.removeAttribute('contenteditable');
    });
  });
  document.addEventListener('input', e => {
    const s = e.target.closest && e.target.closest('.slide');
    if (s) fit(s);
  });

  const toDataUrl = (blob) => new Promise(r => { const f = new FileReader(); f.onload = () => r(f.result); f.readAsDataURL(blob); });

  // Gambar slide (cover, logo header, sponsor) diubah ke data URL & di-decode lebih dulu,
  // supaya html-to-image tidak perlu mengambil ulang dan gambar tidak hilang saat dirender.
  async function inlineImage(img) {
    try {
      if (!img.src.startsWith('data:')) {
        const res = await fetch(img.src, {credentials: 'same-origin'});
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const url = await toDataUrl(await res.blob());
        await new Promise(r => { img.onload = img.onerror = r; img.src = url; });
      }
      await img.decode();
    } catch (err) { console.warn('Gambar gagal disiapkan:', img.src, err); }
  }

  const ready = Promise.all([
    document.fonts.ready,
    ...[...document.querySelectorAll('.slide img')].map(inlineImage),
  ]).then(fitAll);

  // Font Google (Archivo & Plus Jakarta Sans) ditanam sebagai data URL agar ikut ke PNG.
  // Hanya subset latin & latin-ext supaya ringan.
  async function buildFontCss(slide) {
    try {
      const link = document.querySelector('link[href*="fonts.googleapis.com/css"]');
      const css = await (await fetch(link.href)).text();
      const faces = [...css.matchAll(/\/\*\s*([\w-]+)\s*\*\/\s*(@font-face\s*\{[^}]*\})/g)]
        .filter(m => m[1] === 'latin' || m[1] === 'latin-ext').map(m => m[2]);
      if (!faces.length) throw new Error('font CSS kosong');
      let out = faces.join('\n');
      const urls = [...new Set([...out.matchAll(/url\((https:[^)]+)\)/g)].map(m => m[1]))];
      for (const u of urls) out = out.split(u).join(await toDataUrl(await (await fetch(u)).blob()));
      return out;
    } catch (err) {
      console.warn('Embed font manual gagal, pakai bawaan html-to-image', err);
      return htmlToImage.getFontEmbedCSS(slide);
    }
  }

  let fontCss = null;
  // Render pertama sebuah slide sering belum menggambar <img> di dalam SVG (gambar belum
  // ter-decode oleh browser), jadi tiap slide dirender sekali untuk pemanasan lalu dibuang.
  const warmed = new WeakSet();
  // Safari sering memakai font cadangan (lebih lebar) pada render awal SVG foreignObject,
  // sehingga teks meluber keluar kotak. Di Safari pemanasan diulang beberapa kali.
  const isSafari = /^((?!chrome|chromium|android|crios|fxios|edg).)*safari/i.test(navigator.userAgent);
  // PNG untuk unduhan admin; JPG (1080 px) untuk dipublikasikan ke website agar ringan.
  // PDF memakai JPG dengan resolusi pilihan admin (scale) agar ukuran file tetap wajar.
  async function render(it, {jpeg = false, scale = null} = {}) {
    await ready;
    const slide = it.querySelector('.slide');
    if (document.activeElement && slide.contains(document.activeElement)) document.activeElement.blur();
    fontCss ??= await buildFontCss(slide);
    const opts = {
      width: W, height: H, fontEmbedCSS: fontCss, cacheBust: true, backgroundColor: '#0A0A0A',
      pixelRatio: scale ?? (jpeg ? 1 : (+document.getElementById('rg-scale').value || 1)),
      style: {transform: 'none', left: '0', top: '0'},
    };
    if (!warmed.has(slide)) {
      for (let n = isSafari ? 3 : 1; n > 0; n--) {
        await htmlToImage.toCanvas(slide, Object.assign({}, opts, {pixelRatio: 1}));
      }
      warmed.add(slide);
    }
    if (!jpeg) return htmlToImage.toBlob(slide, opts);
    const canvas = await htmlToImage.toCanvas(slide, opts);
    return new Promise(r => canvas.toBlob(r, 'image/jpeg', 0.92));
  }
  const fileName = (it, i) => 'gtr-race-guide-' + String(i + 1).padStart(2, '0') + '-' + it.dataset.name + '.png';
  function save(blob, name) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob); a.download = name;
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 4000);
  }

  items.forEach(it => it.querySelector('.rg-one').addEventListener('click', async e => {
    const btn = e.currentTarget; btn.disabled = true; say('Merender ' + it.dataset.name + '…');
    try {
      const on = items.filter(x => x.querySelector('.rg-inc').checked);
      save(await render(it), fileName(it, Math.max(0, on.indexOf(it))));
      say('');
    } catch (err) { console.error(err); say('Gagal merender slide: ' + (err.message || err)); }
    btn.disabled = false;
  }));

  document.getElementById('rg-all').addEventListener('click', async e => {
    const btn = e.currentTarget; btn.disabled = true;
    try {
      const on = items.filter(it => it.querySelector('.rg-inc').checked);
      const zip = new JSZip();
      for (let i = 0; i < on.length; i++) {
        say('Merender slide ' + (i + 1) + ' dari ' + on.length + '…');
        zip.file(fileName(on[i], i), await render(on[i]));
      }
      say('Menyusun ZIP…');
      save(await zip.generateAsync({type: 'blob'}), 'gtr-race-guide.zip');
      say('Selesai — ' + on.length + ' slide diunduh.');
    } catch (err) { console.error(err); say('Gagal membuat ZIP: ' + (err.message || err)); }
    btn.disabled = false;
  });

  // Satu PDF, satu slide per halaman (rasio 4:5, ukuran halaman 1080×1350 px dalam pt).
  document.getElementById('rg-pdf').addEventListener('click', async e => {
    const btn = e.currentTarget; btn.disabled = true;
    try {
      const on = items.filter(it => it.querySelector('.rg-inc').checked);
      if (!on.length) { say('Pilih minimal satu slide.'); btn.disabled = false; return; }
      const pw = W * 0.75, ph = H * 0.75;
      const pdf = new jspdf.jsPDF({unit: 'pt', format: [pw, ph], orientation: 'portrait', compress: true});
      const scale = +document.getElementById('rg-scale').value || 1;
      for (let i = 0; i < on.length; i++) {
        say('Merender slide ' + (i + 1) + ' dari ' + on.length + '…');
        const img = await toDataUrl(await render(on[i], {jpeg: true, scale}));
        if (i > 0) pdf.addPage([pw, ph], 'portrait');
        pdf.addImage(img, 'JPEG', 0, 0, pw, ph, undefined, 'FAST');
      }
      say('Menyusun PDF…');
      save(pdf.output('blob'), 'gtr-race-guide.pdf');
      say('Selesai — ' + on.length + ' slide dalam 1 PDF.');
    } catch (err) { console.error(err); say('Gagal membuat PDF: ' + (err.message || err)); }
    btn.disabled = false;
  });

  // ===== Publikasi ke website =====
  const csrf = @json(csrf_token());
  const urls = @json(['upload' => route('gtr.race-guide.upload'), 'publish' => route('gtr.race-guide.publish'), 'unpublish' => route('gtr.race-guide.unpublish')]);
  async function post(url, body) {
    const isForm = body instanceof FormData;
    const res = await fetch(url, {
      method: 'POST', credentials: 'same-origin', body: isForm ? body : JSON.stringify(body || {}),
      headers: Object.assign({'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'}, isForm ? {} : {'Content-Type': 'application/json'}),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
    return data;
  }

  const pubBtn = document.getElementById('rg-pub');
  pubBtn.addEventListener('click', async () => {
    const on = items.filter(it => it.querySelector('.rg-inc').checked);
    if (!on.length) return say('Pilih minimal satu slide.');
    if (!confirm('Publikasikan ' + on.length + ' slide sebagai Race Guide final di website?\nVersi yang sedang tayang (jika ada) akan diganti.')) return;
    pubBtn.disabled = true;
    try {
      const batch = Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
      const files = [];
      for (let i = 0; i < on.length; i++) {
        say('Merender & mengunggah slide ' + (i + 1) + ' dari ' + on.length + '…');
        const fd = new FormData();
        fd.append('batch', batch); fd.append('index', i); fd.append('name', on[i].dataset.name);
        fd.append('file', await render(on[i], {jpeg: true}), on[i].dataset.name + '.jpg');
        files.push((await post(urls.upload, fd)).path);
      }
      say('Menyimpan & membuat ZIP…');
      await post(urls.publish, {batch, files});
      say('Race Guide tayang di website (' + on.length + ' slide). Memuat ulang…');
      setTimeout(() => location.reload(), 900);
    } catch (err) { console.error(err); say('Gagal publikasi: ' + (err.message || err)); pubBtn.disabled = false; }
  });

  const unpubBtn = document.getElementById('rg-unpub');
  unpubBtn && unpubBtn.addEventListener('click', async () => {
    if (!confirm('Tarik Race Guide dari website? Menu Race Guide akan disembunyikan dan file yang tayang dihapus.')) return;
    unpubBtn.disabled = true;
    try { await post(urls.unpublish); location.reload(); }
    catch (err) { say('Gagal: ' + (err.message || err)); unpubBtn.disabled = false; }
  });

  // Dipakai untuk uji otomatis: render satu slide ke data URL.
  window.rgRender = async (i) => {
    const blob = await render(items[i]);
    return toDataUrl(blob);
  };
})();
</script>
@endpush
