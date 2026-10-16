{{--
  Course map GTR: peta rute, profil elevasi, pos di rute (WS/CP), segmen & estimasi waktu.
  Dipakai di halaman kategori GTR & Runger App. Butuh $cat (GtrCategory) dan @stack('styles') / @stack('scripts') di layout.
--}}
@push('styles')
@if($cat->route_points)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<style>
  .map-wrap{position:relative}
  .map-stage{position:relative}
  .map-box{position:relative;isolation:isolate;border-radius:18px;overflow:hidden;background:#dfe5d8;box-shadow:0 0 0 1px rgba(0,0,0,.08)}
  #course-map{width:100%;height:420px;background:#dfe5d8}
  /* layar penuh: native (desktop) atau kelas .is-fs (HP / iOS) */
  .map-stage.is-fs{position:fixed;inset:0;z-index:9999;background:#000}
  .map-stage.is-fs .map-box{border-radius:0;height:100%}
  .map-stage.is-fs #course-map{height:100vh;height:100dvh}
  .map-stage.is-fs .elev-profile{display:none}
  .map-hint{display:none;pointer-events:auto;left:50%;bottom:78px;transform:translateX(-50%);padding:8px 14px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;letter-spacing:.03em;white-space:nowrap;cursor:pointer}
  .is-touch .map-hint{display:block}
  .is-touch .map-stage.is-fs .map-hint{display:none}
  .pc-row{display:flex;align-items:center;gap:10px;margin:12px 0 2px}
  .pc-row .pc-range{flex:1;margin:0}
  .pc-step{width:38px;height:38px;flex-shrink:0;border-radius:50%;border:1px solid rgba(255,255,255,.22);background:rgba(255,255,255,.08);color:#fff;font-size:20px;font-weight:700;line-height:1;cursor:pointer;display:grid;place-items:center}
  .pc-step:active{background:rgba(255,255,255,.2)}
  .pc-save{display:flex;gap:8px;margin-top:12px}
  .pc-save-btn{flex:1;padding:11px 14px;border-radius:12px;border:none;background:#E53935;color:#fff;font-weight:800;font-size:12px;letter-spacing:.03em;cursor:pointer}
  .pc-save-btn.saved{background:rgba(255,255,255,.12);color:#86EFAC}
  .pc-band{display:inline-flex;align-items:center;padding:11px 14px;border-radius:12px;background:rgba(255,255,255,.1);color:#fff;font-weight:800;font-size:12px;border:1px solid rgba(255,255,255,.18)}

  /* GPS */
  .map-tools button.on{background:#2563EB}
  .gps-panel{top:12px;left:50%;transform:translateX(-50%);min-width:220px;max-width:calc(100% - 120px);padding:10px 13px;border-radius:14px;font-family:'Archivo',sans-serif}
  .gps-panel[hidden]{display:none}
  .gps-panel .g1{font-weight:900;font-size:16px}
  .gps-panel .g1.off{color:#FDBA74}
  .gps-panel .g2{display:flex;flex-direction:column;gap:2px;margin-top:5px;font-size:11.5px;font-weight:700;color:rgba(255,255,255,.75)}
  .gps-panel .g2 b{color:#fff}
  .gps-dot{width:18px;height:18px;margin:-9px 0 0 -9px;border-radius:50%;background:#2563EB;border:3px solid #fff;box-shadow:0 0 0 6px rgba(37,99,235,.25)}
  /* offline */
  .offline-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:12px 0 0;padding:12px 14px;border-radius:14px;background:#101010;color:#fff}
  .offline-row[hidden]{display:none}
  .offline-row b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:13px}
  .offline-row small{display:block;font-size:11.5px;color:rgba(255,255,255,.65);line-height:1.4;margin-top:2px}
  .offline-row button{flex-shrink:0;padding:10px 14px;border-radius:999px;border:none;background:#E53935;color:#fff;font-weight:800;font-size:11.5px;cursor:pointer;white-space:nowrap}
  .offline-row button.done{background:rgba(34,197,94,.18);color:#86EFAC}
  .course--app .gps-panel{top:auto;bottom:122px;max-width:calc(100% - 24px)}
  .course--app .map-tools [data-act="gps"]{display:grid}

  /* ===== varian app (mobile first) ===== */
  .course--app .map-box{margin:-14px -14px 0;border-radius:20px 20px 0 0;box-shadow:none}
  .course--app #course-map{height:58vh;min-height:340px;max-height:520px}
  .course--app .map-hud{top:10px;left:10px;padding:8px 11px;border-radius:12px}
  .course--app .map-hud .mh-tag{display:none}
  .course--app .map-hud .mh-dist{font-size:20px;margin-top:0}
  .course--app .map-hud .mh-stats{gap:9px;margin-top:4px}
  .course--app .map-tools{top:10px;right:10px}
  .course--app .map-tools button{width:40px;height:40px}
  .course--app .map-tools [data-act="in"],.course--app .map-tools [data-act="out"]{display:none}
  .course--app .map-bar{left:10px;right:10px;bottom:30px;max-width:none;justify-content:space-between}
  .course--app .grade-legend{top:auto;bottom:118px}
  .course--app .map-hint{bottom:74px}
  .course--app .elev-profile{margin:0 -14px;padding:14px 14px 8px 44px;border-radius:0}
  .course--app .elev-head{margin-left:-30px}
  .course--app .elev-svg{height:130px}
  .course--app .elev-readout{margin-bottom:10px}
  .course--app .elev-tip{position:static;display:grid!important;grid-template-columns:repeat(3,1fr);gap:2px 10px;transform:none!important;min-width:0;white-space:normal;padding:10px 12px;border-radius:12px;box-shadow:none;margin-bottom:10px;min-height:62px}
  .course--app .elev-tip:empty{display:flex!important;align-items:center;justify-content:center;background:rgba(255,255,255,.06);border-color:rgba(255,255,255,.08);color:#9A9A9A;font-weight:600;font-size:11.5px}
  .course--app .elev-tip{margin-left:-30px}
  .course--app .elev-tip:empty::before{content:'Geser / ketuk grafik untuk lihat detail tiap KM'}
  .course--app .elev-tip .tt-head{grid-column:1 / -1;margin-bottom:5px;padding-bottom:5px}
  .course--app .elev-tip .tt-row{flex-direction:column;gap:0;line-height:1.35;font-size:10px}
  .course--app .elev-tip .tt-row b{font-size:12px}
  .course--app .elev-chart{touch-action:pan-y}
  .course--app .pt-section{margin-top:22px}
  .course--app .sec-head{margin-bottom:10px}
  .course--app .sec-head h3{font-size:15px}
  .course--app .sec-head span{font-size:11.5px}
  .course--app .pt-grid{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:10px;margin:0 -14px;padding:2px 14px 8px;scrollbar-width:none}
  .course--app .pt-grid::-webkit-scrollbar{display:none}
  .course--app .pt-card{flex:0 0 84%;scroll-snap-align:center}
  .course--app .plan{margin-top:24px}
  .course--app .plan-ctrl{position:sticky;top:64px;z-index:5;margin:0 -4px;padding:14px 14px 10px;border-radius:16px;box-shadow:0 10px 24px rgba(0,0,0,.25)}
  .course--app .pc-val{font-size:28px}
  .course--app .plan-tabs{display:flex;width:100%}
  .course--app .plan-tabs button{flex:1}
  .course--app .pl-stop{padding:11px 12px;gap:10px}
  .course--app .pl-badge{min-width:48px;font-size:10px}
  .course--app .pl-seg{margin-left:30px;padding-left:16px}
  .course--app .pl-seg-in{font-size:11.5px;gap:4px 10px}
  .course--app .split{margin:14px -14px 0;border-radius:0;border-left:none;border-right:none}
  .map-stage:fullscreen{background:#000}
  .map-stage:fullscreen .map-box{border-radius:0}
  .map-stage:fullscreen #course-map{height:100vh}
  /* overlay UI */
  .map-ui{position:absolute;z-index:700;pointer-events:none}
  .map-ui > *{pointer-events:auto}
  .glass{background:rgba(16,16,16,.78);-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.12);color:#fff;box-shadow:0 10px 28px rgba(0,0,0,.3)}
  .map-hud{top:12px;left:12px;border-radius:14px;padding:10px 13px;font-family:'Archivo',sans-serif;max-width:calc(100% - 80px)}
  .map-hud .mh-tag{display:inline-flex;align-items:center;gap:6px;font-weight:800;font-size:9.5px;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.7)}
  .map-hud .mh-tag i{width:7px;height:7px;border-radius:50%;background:var(--c,#E53935)}
  .map-hud .mh-dist{font-weight:900;font-size:26px;line-height:1;margin-top:4px;letter-spacing:-.02em}
  .map-hud .mh-stats{display:flex;gap:12px;margin-top:7px;font-size:10.5px;font-weight:700;color:rgba(255,255,255,.6);white-space:nowrap}
  .map-hud .mh-stats b{color:#fff;font-weight:800;font-size:12.5px}
  .map-tools{top:12px;right:12px;display:flex;flex-direction:column;border-radius:12px;overflow:hidden}
  .map-tools button{width:36px;height:36px;display:grid;place-items:center;color:#fff;background:none;border:none;cursor:pointer;transition:background .2s}
  .map-tools button + button{border-top:1px solid rgba(255,255,255,.1)}
  .map-tools button:hover{background:rgba(255,255,255,.12)}
  .map-tools svg{width:17px;height:17px}
  .map-bar{left:12px;bottom:34px;display:flex;gap:8px;flex-wrap:wrap;max-width:calc(100% - 24px)}
  .seg{display:inline-flex;gap:2px;padding:3px;border-radius:999px}
  .seg button{padding:7px 12px;border-radius:999px;border:none;background:none;color:rgba(255,255,255,.7);font-family:'Archivo',sans-serif;font-weight:700;font-size:10.5px;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;white-space:nowrap;transition:background .2s,color .2s}
  .seg button:hover{color:#fff}
  .seg button.on{background:#fff;color:#101010}
  .grade-legend{top:12px;left:50%;transform:translateX(-50%);display:none;align-items:center;gap:9px;padding:7px 12px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:700;font-size:10px;white-space:nowrap}
  .grade-legend.on{display:flex}
  .grade-legend span{display:inline-flex;align-items:center;gap:4px}
  .grade-legend i{width:14px;height:4px;border-radius:2px}
  /* route */
  /* markers */
  .mk{background:none;border:none}
  .mk > div{position:absolute;left:0;top:0;transform:translate(-50%,-50%);white-space:nowrap;font-family:'Archivo',sans-serif}
  .kmm{width:18px;height:18px;border-radius:50%;display:grid;place-items:center;background:rgba(255,255,255,.95);color:#101010;border:2px solid #101010;font-weight:900;font-size:8.5px;box-shadow:0 2px 6px rgba(0,0,0,.35)}
  /* pin gaya poster */
  .mk > .pin2{transform:translate(-50%,-100%)}
  .pin2{position:relative;display:flex;flex-direction:column;align-items:center;cursor:pointer;filter:drop-shadow(0 3px 6px rgba(0,0,0,.55))}
  .pin2 .ph{min-width:30px;height:30px;padding:0 4px;border-radius:999px;display:grid;place-items:center;background:var(--c);color:#fff;border:2.5px solid #fff;font-family:'Archivo',sans-serif;font-weight:900;font-size:10.5px;letter-spacing:.02em;line-height:1;white-space:nowrap}
  .pin2.sm .ph{font-size:8.5px}
  .pin2 .pt{width:0;height:0;margin-top:-2px;border:7px solid transparent;border-bottom:0;border-top:9px solid #fff}
  .pin2.stem .pt{display:none}
  .mk > .pin2.stem{transform:translate(-50%,-50%)}
  .lblm{pointer-events:auto}
  .mk > .lbl2{transform:none!important}
  .lbl2{display:flex;flex-direction:column;align-items:flex-start;gap:1px;white-space:nowrap;color:#fff;font-family:'Archivo',sans-serif;cursor:pointer;
    text-shadow:0 0 3px rgba(0,0,0,.95),0 0 6px rgba(0,0,0,.7),1px 1px 0 #000,-1px -1px 0 #000,1px -1px 0 #000,-1px 1px 0 #000}
  .lbl2.lt-l{align-items:flex-end;text-align:right}
  .lbl2 .lt{font-weight:900;font-size:12.5px;letter-spacing:.02em;text-transform:uppercase;line-height:1.15}
  .lbl2 .ll{font-weight:800;font-size:11px;line-height:1.25}
  .lbl2 .lh{display:inline-block;padding:3px 9px;margin-bottom:2px;font-weight:900;font-size:11.5px;letter-spacing:.06em;text-shadow:none;color:#fff}
  .lbl2 .lh.go{background:#16A34A}
  .lbl2 .lh.fin{background:#DC2626}
  .lbl2 .lf{display:flex;gap:3px;margin-top:3px;text-shadow:none}
  .lbl2 .lf i{font-style:normal;width:20px;height:20px;border-radius:5px;display:grid;place-items:center;background:#fff;font-size:12px;box-shadow:0 1px 4px rgba(0,0,0,.4)}
  .lbl2 .lf em{font-style:normal;padding:0 6px;height:20px;border-radius:5px;display:grid;place-items:center;background:#DC2626;color:#fff;font-weight:900;font-size:9.5px}
  .arw2{width:24px;height:24px;filter:drop-shadow(0 2px 3px rgba(0,0,0,.7))}
  .arw2 svg{display:block;width:100%;height:100%}
  @media (max-width:560px){ .arw2{width:20px;height:20px} }
  @media (max-width:560px){
    .lbl2 .lt{font-size:11px}
    .lbl2 .ll{font-size:10px}
    .pin2 .ph{min-width:26px;height:26px;font-size:9.5px}
  }
  .sfm{transform:translate(-50%,calc(-100% - 8px))!important;display:flex;align-items:center;gap:7px;padding:6px 11px 6px 7px;border-radius:10px;background:#101010;color:#fff;font-weight:900;font-size:11px;letter-spacing:.08em;box-shadow:0 6px 16px rgba(0,0,0,.4)}
  .sfm::after{content:'';position:absolute;left:50%;bottom:-6px;transform:translateX(-50%);border:6px solid transparent;border-bottom:0;border-top-color:#101010}
  .sfm .fl{width:18px;height:18px;border-radius:4px;background:repeating-conic-gradient(#fff 0 25%,#101010 0 50%) 0 0/9px 9px;border:1.5px solid #fff}
  .sfm.start .fl{background:#22c55e;border-color:#22c55e;display:grid;place-items:center}
  .sfm.start .fl::before{content:'';border:5px solid transparent;border-right:0;border-left:7px solid #fff;margin-left:2px}
  .sfm-dot{width:12px;height:12px;border-radius:50%;background:#fff;border:3px solid #101010}
  .wsm{transform:translate(-50%,calc(-100% + 2px))!important;display:flex;flex-direction:column;align-items:center;cursor:pointer}
  .wsm .b{display:flex;align-items:center;gap:5px;padding:4px 9px 4px 5px;border-radius:999px;background:#2EA6E6;color:#fff;font-weight:800;font-size:10.5px;box-shadow:0 4px 12px rgba(0,0,0,.35);border:2px solid #fff}
  .wsm .b svg{width:14px;height:14px}
  .wsm .s{width:2px;height:8px;background:#fff}
  .sum{transform:translate(-50%,calc(-100% - 4px))!important;display:flex;flex-direction:column;align-items:center}
  .sum .b{display:flex;align-items:center;gap:5px;padding:4px 9px;border-radius:7px;background:#fff;color:#101010;font-weight:900;font-size:10.5px;box-shadow:0 4px 12px rgba(0,0,0,.3)}
  .sum .b::before{content:'';border:5px solid transparent;border-top:0;border-bottom:8px solid #E53935}
  .sum .s{width:0;height:0;border:5px solid transparent;border-bottom:0;border-top-color:#fff}
  @media (max-width:560px){
    #course-map{height:380px}
    .map-hud{padding:8px 11px}
    .map-hud .mh-dist{font-size:21px}
    .map-hud .mh-stats{gap:9px}
    .map-bar{flex-wrap:nowrap;overflow-x:auto;scrollbar-width:none;gap:6px}
    .seg button{padding:7px 9px;font-size:10px;letter-spacing:.04em}
    .grade-legend{top:auto;bottom:78px;font-size:9px;gap:6px}
  }
  .leaflet-container{font-family:'Plus Jakarta Sans',sans-serif}
  .leaflet-tooltip.ws-tip{background:#101010;color:#fff;border:none;font-weight:700;font-size:11px;border-radius:6px}
  .leaflet-tooltip.ws-tip::before{border-top-color:#101010}
  .ws-divicon{background:none;border:none;filter:drop-shadow(0 2px 5px rgba(0,0,0,.45));cursor:pointer}
  .leaflet-popup.ws-popup .leaflet-popup-content-wrapper{background:#101010;color:#fff;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.45)}
  .leaflet-popup.ws-popup .leaflet-popup-content{margin:8px 13px;font-family:'Archivo',sans-serif;font-weight:700;font-size:12px}
  .leaflet-popup.ws-popup .leaflet-popup-tip{background:#101010}
  /* elevation profile */
  .elev-profile{margin-top:18px;padding:16px 18px 12px 48px;border-radius:18px;background:#0E0E0E;color:#fff}
  .elev-head{display:flex;justify-content:space-between;align-items:baseline;margin:0 0 10px -30px}
  .elev-title{font-family:'Archivo',sans-serif;font-weight:900;font-size:14px;text-transform:uppercase;letter-spacing:.04em;color:#fff}
  .elev-meta{font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;color:#9A9A9A}
  .elev-pins{position:relative;margin-bottom:4px}
  .elev-pins .ep{position:absolute;transform:translateX(-50%);padding:3px 7px;border-radius:6px;background:var(--c);color:#fff;font-family:'Archivo',sans-serif;font-weight:900;font-size:10.5px;letter-spacing:.03em;line-height:1.2;white-space:nowrap;box-shadow:0 2px 6px rgba(0,0,0,.4)}
  .elev-chart{position:relative;cursor:crosshair}
  .elev-svg{width:100%;height:170px;display:block;border-bottom:1px solid rgba(255,255,255,.14);overflow:visible}
  .elev-gl{position:absolute;right:calc(100% + 8px);transform:translateY(-50%);font-family:'Archivo',sans-serif;font-size:10px;font-weight:700;color:#7A7A7A;white-space:nowrap;pointer-events:none}
  .elev-markers .ed{position:absolute;width:11px;height:11px;border-radius:50%;background:var(--c);border:2px solid #fff;transform:translate(-50%,-50%);box-shadow:0 1px 4px rgba(0,0,0,.5)}
  .elev-axis2{position:relative;height:18px;margin-top:6px}
  .elev-axis2 span{position:absolute;transform:translateX(-50%);font-family:'Archivo',sans-serif;font-size:10.5px;font-weight:700;color:#7A7A7A;white-space:nowrap}
  .elev-axis2 span:first-child{transform:none}
  .elev-axis2 span:last-child:not(:first-child){transform:translateX(-100%)}
  @media (max-width:560px){
    .elev-axis2 span:nth-child(even){display:none}
    .elev-pins .ep{font-size:9.5px;padding:2px 6px}
  }
  .elev-cursor{position:absolute;top:0;bottom:0;width:0;border-left:1.5px solid rgba(255,255,255,.85);display:none;pointer-events:none;transform:translateX(-50%);z-index:2}
  .elev-cursor .cdot{position:absolute;left:50%;top:0;transform:translate(-50%,-50%);width:12px;height:12px;border-radius:50%;background:#101010;border:2.5px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.6)}
  .elev-tip{position:absolute;top:-6px;transform:translate(-50%,calc(-100% - 12px));background:#1C1C1C;border:1px solid rgba(255,255,255,.14);color:#fff;font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;padding:9px 11px;border-radius:10px;white-space:nowrap;display:none;pointer-events:none;z-index:4;box-shadow:0 10px 26px rgba(0,0,0,.35);min-width:150px}
  .elev-tip.al{transform:translate(-12px,calc(-100% - 12px))}
  .elev-tip.ar{transform:translate(calc(-100% + 12px),calc(-100% - 12px))}
  .elev-tip .tt-head{display:flex;align-items:baseline;justify-content:space-between;gap:12px;padding-bottom:7px;margin-bottom:7px;border-bottom:1px solid rgba(255,255,255,.12)}
  .elev-tip .tt-km{font-weight:900;font-size:15px;letter-spacing:-.01em}
  .elev-tip .tt-ele{font-weight:800;font-size:13px;color:#fff}
  .elev-tip .tt-row{display:flex;justify-content:space-between;gap:14px;font-size:11px;line-height:1.75;color:rgba(255,255,255,.6);font-weight:600}
  .elev-tip .tt-row b{color:#fff;font-weight:800}
  .elev-tip .up{color:#FF7A6B!important}
  .elev-tip .dn{color:#5CC8FF!important}
  .elev-tip .flat{color:#B4B4B4!important}
  .elev-ymax,.elev-ymin{position:absolute;left:6px;font-family:'Archivo',sans-serif;font-size:10.5px;font-weight:700;color:#8A8A8A;background:rgba(255,255,255,.72);padding:1px 5px;border-radius:5px}
  .elev-ymax{top:4px}
  .elev-ymin{bottom:6px}
  .elev-markers{position:absolute;inset:0;pointer-events:none}
  .ws-mark{position:absolute;top:0;bottom:0;transform:translateX(-50%)}
  .ws-mark::before{content:'';position:absolute;top:14px;bottom:0;left:50%;width:0;border-left:1.5px dashed rgba(46,166,230,.6)}
  .ws-pin{position:absolute;top:-3px;left:50%;transform:translateX(-50%);width:18px;height:18px;filter:drop-shadow(0 2px 4px rgba(0,0,0,.35))}
  .ws-pin svg{width:100%;height:100%;display:block}
  .elev-axis{display:flex;justify-content:space-between;font-family:'Archivo',sans-serif;font-size:11px;color:#9A9A9A;margin-top:6px}
  .ws-mark.cp::before{border-left-color:rgba(249,115,22,.6)}
  /* section heads */
  .gpx-lock{display:inline-flex;align-items:center;gap:8px;padding:12px 18px;border-radius:999px;background:#fff;border:1px solid #E2E2E2;font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#8A8A8A}
  .gpx-lock svg{width:16px;height:16px}
  .sec-head{display:flex;flex-wrap:wrap;align-items:baseline;justify-content:space-between;gap:4px 14px;margin-bottom:12px}
  .sec-head h3{font-family:'Archivo',sans-serif;font-weight:900;font-size:17px;text-transform:uppercase;letter-spacing:.01em;color:#101010}
  .sec-head span{font-size:12.5px;color:#8A8A8A}
  /* pos cards */
  .pt-section{margin-top:26px}
  .pt-grid{display:grid;grid-template-columns:1fr;gap:10px}
  .pt-card{--pc:#2EA6E6;display:flex;flex-direction:column;gap:8px;text-align:left;padding:14px 16px;border-radius:16px;background:#F7F7F7;border:1px solid #EFEFEF;border-left:4px solid var(--pc);cursor:pointer;font:inherit;color:#101010;transition:background .2s,border-color .2s,box-shadow .2s}
  .pt-card.cp{--pc:#F97316}
  .pt-card:hover,.pt-card.on{background:#fff;border-color:#E2E2E2;border-left-color:var(--pc);box-shadow:0 8px 22px rgba(0,0,0,.07)}
  .pt-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
  .pt-code{display:inline-flex;align-items:center;gap:6px;padding:4px 10px 4px 6px;border-radius:999px;background:color-mix(in srgb,var(--pc) 14%,transparent);color:color-mix(in srgb,var(--pc) 80%,#101010);font-family:'Archivo',sans-serif;font-weight:900;font-size:11.5px;letter-spacing:.04em}
  .pt-code svg{width:15px;height:15px}
  .pt-km{font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;color:#8A8A8A}
  .pt-title{font-family:'Archivo',sans-serif;font-weight:800;font-size:15.5px;line-height:1.25}
  .pt-row{display:flex;gap:7px;align-items:flex-start;font-size:12.5px;color:#555;line-height:1.45}
  .pt-row svg{width:14px;height:14px;flex-shrink:0;margin-top:2px;color:#9A9A9A}
  .pt-row.cut{color:#B42318;font-weight:700}
  .pt-row.cut svg{color:#E53935}
  .pt-fac{display:flex;flex-wrap:wrap;gap:5px}
  .pt-fac span{padding:4px 9px;border-radius:999px;background:#fff;border:1px solid #E6E6E6;font-size:11.5px;font-weight:600;color:#333}
  .pt-note{font-size:12px;color:#6B6B6B;line-height:1.5;padding:8px 10px;border-radius:10px;background:#fff;border:1px dashed #E0E0E0}
  /* map popup */
  .leaflet-popup.pt-popup .leaflet-popup-content-wrapper{border-radius:14px;box-shadow:0 14px 34px rgba(0,0,0,.28);padding:0}
  .leaflet-popup.pt-popup .leaflet-popup-content{margin:0;width:240px!important;font-family:'Plus Jakarta Sans',sans-serif}
  .pp{padding:12px 14px 13px;border-top:4px solid var(--pc,#2EA6E6);border-radius:14px}
  .pp-h{display:flex;justify-content:space-between;align-items:center;gap:8px;font-family:'Archivo',sans-serif}
  .pp-code{font-weight:900;font-size:11px;letter-spacing:.06em;padding:3px 8px;border-radius:999px;color:#fff;background:var(--pc,#2EA6E6)}
  .pp-km{font-weight:800;font-size:11.5px;color:#8A8A8A}
  .pp-t{font-family:'Archivo',sans-serif;font-weight:900;font-size:15px;color:#101010;margin-top:7px;line-height:1.2}
  .pp-r{display:flex;gap:6px;font-size:12px;color:#555;margin-top:6px;line-height:1.4}
  .pp-r svg{width:13px;height:13px;flex-shrink:0;margin-top:2px;color:#9A9A9A}
  .pp-r.cut{color:#B42318;font-weight:700}
  .pp-fac{display:flex;flex-wrap:wrap;gap:4px;margin-top:8px}
  .pp-fac span{padding:3px 8px;border-radius:999px;background:#F3F3F3;font-size:11px;font-weight:600;color:#333}
  .pp-n{font-size:11.5px;color:#6B6B6B;margin-top:8px;line-height:1.45}
  /* info & bahaya jalur */
  .poi-section{margin-top:26px}
  .poi-list{display:grid;grid-template-columns:1fr;gap:8px}
  .poi-item{display:flex;gap:12px;align-items:flex-start;text-align:left;padding:12px 14px;border-radius:14px;background:#F7F7F7;border:1px solid #EFEFEF;cursor:pointer;font:inherit;color:#101010;transition:background .2s,border-color .2s}
  .poi-item:hover{background:#fff;border-color:#E2E2E2}
  .poi-item.hz{background:#FFF7ED;border-color:#FED7AA}
  .poi-ic{width:34px;height:34px;flex-shrink:0;border-radius:10px;display:grid;place-items:center;background:#fff;font-size:17px;box-shadow:0 1px 3px rgba(0,0,0,.08)}
  .poi-tx{display:flex;flex-direction:column;gap:2px;min-width:0}
  .poi-tx b{font-family:'Archivo',sans-serif;font-weight:800;font-size:14px}
  .poi-tx small{font-size:11.5px;color:#8A8A8A;font-weight:600}
  .poi-note{font-size:12px;color:#555;line-height:1.45;margin-top:3px}
  .poim{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;background:#fff;border:2px solid #374151;font-size:14px;box-shadow:0 3px 8px rgba(0,0,0,.35);cursor:pointer}
  .poim.hz{border-color:#F97316;background:#FFF7ED}
  .sp-hz{display:flex;gap:4px;flex-wrap:wrap;margin-top:3px;font-size:12px}
  .sp-hz span{padding:1px 6px;border-radius:999px;background:#FFF7ED;border:1px solid #FED7AA;font-size:10px;font-weight:700;color:#C2410C;white-space:nowrap}
  @media (min-width:820px){ .poi-list{grid-template-columns:repeat(2,1fr)} }
  .course--app .poi-section{margin-top:22px}
  /* plan / estimator */
  .plan{margin-top:30px}
  .plan-ctrl{padding:16px 18px 12px;border-radius:18px;background:#101010;color:#fff}
  .pc-top{display:flex;justify-content:space-between;align-items:flex-end;gap:12px}
  .pc-lab{font-family:'Archivo',sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:rgba(255,255,255,.55);font-weight:700}
  .pc-val{font-family:'Archivo',sans-serif;font-weight:900;font-size:32px;line-height:1;margin-top:5px;letter-spacing:-.01em}
  .pc-side{text-align:right}
  .pc-pace{font-family:'Archivo',sans-serif;font-weight:800;font-size:15px;margin-top:5px}
  .pc-range{width:100%;margin:14px 0 4px;accent-color:#E53935;height:22px;cursor:pointer}
  .pc-scale{display:flex;justify-content:space-between;font-family:'Archivo',sans-serif;font-size:10.5px;font-weight:700;color:rgba(255,255,255,.45)}
  .plan-list{margin-top:14px;display:flex;flex-direction:column}
  .plan-list[hidden],.plan-tabs[hidden]{display:none}
  .pl-stop{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:12px;padding:12px 14px;border-radius:14px;background:#F7F7F7;border:1px solid #EFEFEF}
  .pl-badge{min-width:54px;text-align:center;padding:5px 8px;border-radius:9px;font-family:'Archivo',sans-serif;font-weight:900;font-size:11px;letter-spacing:.04em;color:#fff;background:var(--pc,#101010)}
  .pl-name{font-family:'Archivo',sans-serif;font-weight:800;font-size:14px;color:#101010;line-height:1.2}
  .pl-sub{font-size:11.5px;color:#8A8A8A;margin-top:2px}
  .pl-time{text-align:right}
  .pl-eta{font-family:'Archivo',sans-serif;font-weight:900;font-size:17px;color:#101010;line-height:1}
  .pl-el{font-size:11px;color:#8A8A8A;margin-top:3px}
  .pl-cot{display:inline-block;margin-top:5px;padding:2px 8px;border-radius:999px;font-size:10.5px;font-weight:800;white-space:nowrap}
  .pl-cot.ok{background:#DCFCE7;color:#15803D}
  .pl-cot.warn{background:#FEF3C7;color:#B45309}
  .pl-cot.bad{background:#FEE2E2;color:#B91C1C}
  .pl-seg{position:relative;margin-left:40px;padding:10px 0 10px 22px;border-left:2px dashed #DADADA}
  .pl-seg-in{display:flex;flex-wrap:wrap;align-items:center;gap:6px 12px;font-size:12px;color:#555}
  .pl-seg-in b{color:#101010;font-family:'Archivo',sans-serif;font-weight:800}
  .pl-seg .up{color:#DC2626;font-weight:700}
  .pl-seg .dn{color:#2563EB;font-weight:700}
  .pl-tag{padding:3px 9px;border-radius:999px;font-size:10.5px;font-weight:800;background:#EFEFEF;color:#444}
  .pl-tag.hard{background:#FEE2E2;color:#B91C1C}
  .pl-tag.mid{background:#FFEDD5;color:#C2410C}
  .pl-tag.down{background:#DBEAFE;color:#1D4ED8}
  .pl-dur{margin-left:auto;font-family:'Archivo',sans-serif;font-weight:800;color:#101010}
  .plan-tabs{display:inline-flex;gap:3px;padding:4px;margin-top:14px;border-radius:999px;background:#F2F2F2}
  .plan-tabs button{padding:8px 16px;border-radius:999px;border:none;background:none;font-family:'Archivo',sans-serif;font-weight:800;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#7A7A7A;cursor:pointer}
  .plan-tabs button.on{background:#101010;color:#fff}
  /* split per KM */
  .split{margin-top:14px;border:1px solid #EFEFEF;border-radius:16px;overflow:hidden}
  .split[hidden]{display:none}
  .sp-row{display:grid;grid-template-columns:62px 92px 70px 1fr 74px;align-items:center;gap:10px;padding:10px 14px;border-top:1px solid #F0F0F0;font-size:12.5px;color:#444}
  .sp-row:first-child{border-top:none}
  .sp-head{background:#FAFAFA;font-family:'Archivo',sans-serif;font-weight:800;font-size:10px;letter-spacing:.12em;text-transform:uppercase;color:#9A9A9A}
  .sp-row .r{text-align:right}
  .sp-km{display:flex;align-items:center;gap:5px;font-family:'Archivo',sans-serif;font-weight:900;font-size:14px;color:#101010}
  .sp-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
  .sp-ele{display:flex;flex-direction:column;gap:1px;font-size:11.5px;line-height:1.25}
  .sp-ele .up{color:#DC2626;font-weight:700}
  .sp-ele .dn{color:#2563EB;font-weight:700}
  .sp-grade{font-family:'Archivo',sans-serif;font-weight:800;font-size:12.5px}
  .sp-pace{display:flex;align-items:center;gap:10px;min-width:0}
  .sp-pace b{font-family:'Archivo',sans-serif;font-weight:900;font-size:14px;color:#101010;min-width:42px}
  .sp-bar{flex:1;height:7px;border-radius:4px;background:#F0F0F0;overflow:hidden;min-width:20px}
  .sp-bar i{display:block;height:100%;border-radius:4px}
  .sp-time b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;color:#101010}
  .sp-time small{font-size:10.5px;color:#9A9A9A}
  .sp-pts{display:flex;gap:3px;flex-wrap:wrap;margin-top:3px}
  .sp-pts span{padding:1px 6px;border-radius:999px;font-family:'Archivo',sans-serif;font-weight:900;font-size:9px;color:#fff}
  .sp-row.has-pt{background:#FCFCFC}
  .sp-sum{display:grid;grid-template-columns:62px 92px 70px 1fr 74px;gap:10px;padding:12px 14px;background:#101010;color:#fff;font-size:12px;align-items:center}
  .sp-sum b{font-family:'Archivo',sans-serif;font-weight:900}
  @media (max-width:560px){
    .sp-row,.sp-sum{grid-template-columns:44px 58px 1fr 54px;gap:8px;padding:10px 12px}
    .sp-row > :nth-child(3),.sp-sum > :nth-child(3){display:none}
    .sp-bar{display:none}
    .sp-km{font-size:13px}
  }
  @media (min-width:820px){
    .pt-grid{grid-template-columns:repeat(3,1fr)}
    .plan-ctrl{padding:18px 22px 14px}
  }
  .ws-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
  .ws-chip{display:inline-flex;align-items:center;gap:7px;padding:7px 12px;border-radius:999px;background:#F6F6F6;border:1px solid #ECECEC;font-size:12.5px;color:#101010;font-weight:600}
  .ws-chip svg{width:15px;height:15px;flex-shrink:0}

  /* profil elevasi: kartu terpisah di bawah peta */
  .map-stage:fullscreen .elev-profile{display:none}
  @media (min-width:820px){
    #course-map{height:560px}
    .elev-profile{margin-top:14px;padding:18px 22px 14px 58px}
    .elev-profile .elev-svg{height:200px}
  }
</style>
@endif
@endpush

<div class="course{{ ($variant ?? 'web') === 'app' ? ' course--app' : '' }}">
@if($cat->route_points)
  @php
    $points = $cat->coursePoints();
    $course = $cat->courseSegments();
    $fmtKm = fn ($v) => rtrim(rtrim(number_format((float) $v, 1), '0'), '.');

    $ep = $cat->elevation_profile ?? [];
    $elev = null;
    if (count($ep) > 1) {
      $kms = array_map(fn ($p) => (float) $p[0], $ep);
      $eles = array_map(fn ($p) => (float) $p[1], $ep);
      $minKm = min($kms); $maxKm = max($kms);
      $minEle = min($eles); $maxEle = max($eles);
      // sumbu ketinggian dibulatkan ke kelipatan 50/100 m (seperti di poster)
      $eStep = ($maxEle - $minEle) > 400 ? 100 : 50;
      $lo = floor($minEle / $eStep) * $eStep; $hi = ceil($maxEle / $eStep) * $eStep;
      if ($hi <= $lo) { $hi = $lo + $eStep; }
      $spanKm = max(0.001, $maxKm - $minKm);
      $spanEle = max(1, $hi - $lo);
      $W = 1000; $H = 220; $padT = 16; $padB = 22;
      $fx = fn ($km) => ($km - $minKm) / $spanKm * $W;
      $fy = fn ($e) => $H - $padB - (($e - $lo) / $spanEle) * ($H - $padT - $padB);
      $grid = [];
      for ($e = $lo; $e <= $hi + .01; $e += $eStep) { $grid[] = ['e' => (int) $e, 'top' => round($fy($e) / $H * 100, 2), 'y' => round($fy($e), 1)]; }
      $kStep = $maxKm > 25 ? 5 : ($maxKm > 12 ? 2 : 1);
      $ticks = [];
      for ($k = 0; $k <= $maxKm + .01; $k += $kStep) { $ticks[] = ['k' => $k, 'left' => round(($k - $minKm) / $spanKm * 100, 2)]; }
      $pts = [];
      foreach ($ep as $p) { $pts[] = round($fx((float) $p[0]), 1) . ',' . round($fy((float) $p[1]), 1); }
      $line = 'M ' . implode(' L ', $pts);
      $area = $line . ' L ' . $W . ',' . ($H - $padB) . ' L 0,' . ($H - $padB) . ' Z';
      // penanda pos: posisi %, ketinggian di kurva, baris label (bertingkat bila berdekatan)
      $eleAtKm = function ($km) use ($ep) {
          for ($i = 1; $i < count($ep); $i++) {
              if ((float) $ep[$i][0] >= $km) {
                  $k0 = (float) $ep[$i - 1][0]; $k1 = (float) $ep[$i][0];
                  return $k1 > $k0 ? $ep[$i - 1][1] + ($ep[$i][1] - $ep[$i - 1][1]) * ($km - $k0) / ($k1 - $k0) : $ep[$i][1];
              }
          }
          return (float) $ep[count($ep) - 1][1];
      };
      $rowsEnd = [];
      $marks = $points->map(function ($w) use ($minKm, $spanKm, $fy, $H, $eleAtKm, &$rowsEnd) {
          $left = max(0, min(100, ($w['km'] - $minKm) / $spanKm * 100));
          $row = 0;
          while (isset($rowsEnd[$row]) && $left - $rowsEnd[$row] < 8) { $row++; }
          $rowsEnd[$row] = $left;
          return $w + ['left' => round($left, 2), 'x' => round($left * 10, 1), 'top' => round($fy($eleAtKm($w['km'])) / $H * 100, 2), 'row' => $row];
      })->all();
      $pinRows = $rowsEnd ? count($rowsEnd) : 0;
      $elev = compact('kms', 'eles', 'minKm', 'maxKm', 'minEle', 'maxEle', 'lo', 'hi', 'line', 'area', 'grid', 'ticks', 'marks', 'pinRows');
    }
    $drop = '<svg viewBox="0 0 24 24" fill="#2EA6E6" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3c0 0-6 6.6-6 11a6 6 0 0 0 12 0c0-4.4-6-11-6-11z"/></svg>';
    $flag = '<svg viewBox="0 0 24 24" fill="#F97316" stroke="#fff" stroke-width="1.6" stroke-linejoin="round"><path d="M5 21V4h11l-2 4 2 4H5"/></svg>';
    $ptColor = ['ws' => '#2EA6E6', 'cp' => '#F97316'];
  @endphp

  <div class="map-wrap">
  <div class="map-stage">
  <div class="map-box">
  <div id="course-map"
       data-points='@json($cat->route_points)'
       data-color="{{ $cat->color }}"
       data-ws='@json($points)'
       data-pois='@json($cat->coursePois())'
       data-ep='@json($cat->elevation_profile ?? [])'></div>

  <div class="map-ui map-hud glass">
    <span class="mh-tag" style="--c:{{ $cat->color }}"><i></i>{{ $cat->tag }} · Course</span>
    <div class="mh-dist">{{ $cat->distance }}</div>
    <div class="mh-stats">
      <span><b data-route-km>-</b> km</span>
      <span><b>{{ $cat->elevation_gain ?: '-' }}</b> D+</span>
      @if($elev)<span><b>{{ round($elev['maxEle']) }}</b> mdpl</span>@endif
    </div>
  </div>

  <div class="map-ui map-tools glass">
    <button type="button" data-act="in" aria-label="Zoom in"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
    <button type="button" data-act="out" aria-label="Zoom out"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><line x1="5" y1="12" x2="19" y2="12"/></svg></button>
    <button type="button" data-act="fit" aria-label="Tampilkan seluruh rute"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/><circle cx="12" cy="12" r="8"/></svg></button>
    <button type="button" data-act="gps" aria-label="Lokasi saya"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s7-6.2 7-12a7 7 0 0 0-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="10" r="2.5"/></svg></button>
    <button type="button" data-act="fs" aria-label="Layar penuh"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3M21 8V5a2 2 0 0 0-2-2h-3M3 16v3a2 2 0 0 0 2 2h3M16 21h3a2 2 0 0 0 2-2v-3"/></svg></button>
  </div>

  <div class="map-ui gps-panel glass" data-gps-panel hidden></div>
  <div class="map-ui grade-legend glass" id="grade-legend">
    <span><i style="background:#2563EB"></i>Turun</span>
    <span><i style="background:#22C55E"></i>&lt;3%</span>
    <span><i style="background:#FACC15"></i>3–8%</span>
    <span><i style="background:#F97316"></i>8–15%</span>
    <span><i style="background:#DC2626"></i>&gt;15%</span>
  </div>

  <button type="button" class="map-ui map-hint glass" data-act="fs">Ketuk untuk jelajahi peta</button>
  <div class="map-ui map-bar">
    <div class="seg glass" id="map-layers"></div>
    @if($elev)
    <div class="seg glass" id="map-mode">
      <button type="button" class="on" data-mode="route">Rute</button>
      <button type="button" data-mode="grade">Gradien</button>
    </div>
    @endif
  </div>
  </div>

  @if($elev)
  <div class="elev-profile">
    <div class="elev-head">
      <span class="elev-title">Profil Elevasi</span>
      <span class="elev-meta">{{ $cat->elevation_gain ?: '-' }} · {{ $fmtKm($elev['maxKm']) }} km</span>
    </div>
    @if($elev['pinRows'])
    <div class="elev-pins" style="height:{{ $elev['pinRows'] * 24 + 4 }}px">
      @foreach($elev['marks'] as $w)
        <span class="ep" style="left:{{ $w['left'] }}%;bottom:{{ $w['row'] * 24 }}px;--c:{{ $ptColor[$w['type']] }}" title="{{ $w['code'] }} · {{ $w['title'] }} · KM {{ $fmtKm($w['km']) }}">{{ $w['code'] }}</span>
      @endforeach
    </div>
    @endif
    <div class="elev-chart"
         data-ep='@json($cat->elevation_profile)'
         data-minkm="{{ $elev['minKm'] }}" data-maxkm="{{ $elev['maxKm'] }}"
         data-minele="{{ $elev['lo'] }}" data-maxele="{{ $elev['hi'] }}">
      <svg class="elev-svg" viewBox="0 0 1000 220" preserveAspectRatio="none">
        <defs>
          <linearGradient id="elevGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="{{ $cat->color }}" stop-opacity=".7"/>
            <stop offset="1" stop-color="{{ $cat->color }}" stop-opacity=".06"/>
          </linearGradient>
        </defs>
        @foreach($elev['grid'] as $g)
          <line x1="0" y1="{{ $g['y'] }}" x2="1000" y2="{{ $g['y'] }}" stroke="#fff" stroke-opacity=".08" stroke-width="1" vector-effect="non-scaling-stroke"/>
        @endforeach
        <path d="{{ $elev['area'] }}" fill="url(#elevGrad)"/>
        <path d="{{ $elev['line'] }}" fill="none" stroke="#fff" stroke-opacity=".25" stroke-width="6" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
        <path d="{{ $elev['line'] }}" fill="none" stroke="#fff" stroke-width="2.5" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
        @foreach($elev['marks'] as $w)
          <line x1="{{ $w['x'] }}" y1="0" x2="{{ $w['x'] }}" y2="198" stroke="{{ $ptColor[$w['type']] }}" stroke-width="1.5" stroke-dasharray="5 5" vector-effect="non-scaling-stroke"/>
        @endforeach
      </svg>
      @foreach($elev['grid'] as $g)
        <span class="elev-gl" style="top:{{ $g['top'] }}%">{{ $g['e'] }} m</span>
      @endforeach
      <div class="elev-markers">
        @foreach($elev['marks'] as $w)
          <span class="ed" style="left:{{ $w['left'] }}%;top:{{ $w['top'] }}%;--c:{{ $ptColor[$w['type']] }}"></span>
        @endforeach
      </div>
      <div class="elev-cursor"><span class="cdot"></span></div>
      <div class="elev-tip"></div>
    </div>
    <div class="elev-axis2">
      @foreach($elev['ticks'] as $tk)
        <span style="left:{{ $tk['left'] }}%">{{ $fmtKm($tk['k']) }} km</span>
      @endforeach
    </div>
  </div>
  @endif
  </div>
  </div>

  @if(($variant ?? 'web') === 'app')
  <div class="offline-row" data-offline hidden>
    <div>
      <b>Mode race day</b>
      <small data-offline-msg>Simpan halaman & peta rute agar tetap bisa dibuka tanpa sinyal.</small>
    </div>
    <button type="button" data-offline-btn>Simpan offline</button>
  </div>
  @endif

  @if($points->count())
  <div class="pt-section">
    <div class="sec-head">
      <h3>Pos di Rute</h3>
      <span>Klik pos untuk melihat lokasinya di peta</span>
    </div>
    <div class="pt-grid">
      @foreach($points as $i => $w)
        <button type="button" class="pt-card {{ $w['type'] }}" data-pt="{{ $i }}">
          <div class="pt-top">
            <span class="pt-code">{!! $w['type'] === 'cp' ? $flag : $drop !!}{{ $w['code'] }}</span>
            <span class="pt-km">KM {{ $fmtKm($w['km']) }}</span>
          </div>
          <div class="pt-title">{{ $w['title'] }}</div>
          @if($w['location'])
            <div class="pt-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>{{ $w['location'] }}</div>
          @endif
          @if($w['cutoff'])
            <div class="pt-row cut"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>Cut-off {{ $w['cutoff'] }} WITA</div>
          @endif
          @if(count($w['facilities']))
            <div class="pt-fac">@foreach($w['facilities'] as $f)<span>{{ $f }}</span>@endforeach</div>
          @endif
          @if($w['notes'])
            <div class="pt-note">{{ $w['notes'] }}</div>
          @endif
        </button>
      @endforeach
    </div>
  </div>
  @endif

  @php $pois = $cat->coursePois(); @endphp
  @if($pois->count())
  <div class="poi-section">
    <div class="sec-head">
      <h3>Info &amp; Bahaya Jalur</h3>
      <span>Perhatikan titik-titik ini saat berlari</span>
    </div>
    <div class="poi-list">
      @foreach($pois as $i => $p)
        <button type="button" class="poi-item{{ $p['hazard'] ? ' hz' : '' }}" data-poi="{{ $i }}">
          <span class="poi-ic">{{ $p['icon'] }}</span>
          <span class="poi-tx">
            <b>{{ $p['title'] }}</b>
            <small>KM {{ $fmtKm($p['km']) }}{{ $p['km_end'] ? '–' . $fmtKm($p['km_end']) : '' }} · {{ $p['label'] }}</small>
            @if($p['note'])<span class="poi-note">{{ $p['note'] }}</span>@endif
          </span>
        </button>
      @endforeach
    </div>
  </div>
  @endif

  @if(!empty($course['segments']))
  <div class="plan" id="race-plan"
       data-course='@json($course)'
       data-start="{{ $cat->startMinutes() }}"
       data-cot="{{ $cat->cutOffMinutes() }}"
       data-saved="{{ isset($registration) && $registration ? $registration->target_finish_minutes : '' }}"
       data-saved-mode="{{ isset($registration) && $registration ? ($registration->race_plan_mode ?: 'pos') : '' }}">
    <div class="sec-head">
      <h3>Segmen &amp; Estimasi Waktu</h3>
      <span>Atur target finish untuk melihat perkiraan jam tiba di tiap pos</span>
    </div>
    <div class="plan-ctrl">
      <div class="pc-top">
        <div>
          <div class="pc-lab">Target Finish</div>
          <div class="pc-val" data-plan-target>-</div>
        </div>
        <div class="pc-side">
          <div class="pc-lab">Rata-rata</div>
          <div class="pc-pace" data-plan-pace>-</div>
        </div>
      </div>
      <div class="pc-row">
        <button type="button" class="pc-step" data-plan-step="-5" aria-label="Kurangi 5 menit">−</button>
        <input type="range" class="pc-range" data-plan-range step="5" aria-label="Target waktu finish">
        <button type="button" class="pc-step" data-plan-step="5" aria-label="Tambah 5 menit">+</button>
      </div>
      <div class="pc-scale"><span data-plan-min></span><span data-plan-max></span></div>
      @if(isset($registration) && $registration)
        <form method="POST" action="{{ route('gtr.account.race.plan', $registration) }}" class="pc-save">
          @csrf
          <input type="hidden" name="target_finish_minutes" data-plan-input>
          <input type="hidden" name="race_plan_mode" value="{{ $registration->race_plan_mode ?: 'pos' }}" data-plan-mode>
          <button type="submit" class="pc-save-btn" data-plan-save>Simpan sebagai Race Plan</button>
          <a class="pc-band" href="{{ route('gtr.account.race.paceband', $registration) }}" data-plan-band>Pace Band</a>
        </form>
      @endif
    </div>
    <div class="plan-tabs" role="tablist">
      <button type="button" class="on" data-plan-view="seg">Per Pos</button>
      <button type="button" data-plan-view="km">Per KM</button>
    </div>
    <div class="plan-list" data-plan-list></div>
    <div class="split" data-plan-splits hidden>
      <div class="sp-row sp-head">
        <span>KM</span><span>Elevasi</span><span>Gradien</span><span class="sp-pace-h">Pace</span><span class="r">Waktu</span>
      </div>
      <div data-plan-split-rows></div>
    </div>
    <p class="tab-note">Estimasi dihitung dari jarak &amp; elevasi (tiap 100 m tanjakan ≈ 1 km lari datar). Sesuaikan dengan kemampuanmu.</p>
  </div>
  @endif

  <p class="tab-note">Rute {{ $cat->distance }} digambar dari file GPX resmi.</p>
@else
  <img class="tab-img" src="{{ $cat->header_url }}" alt="Course map {{ $cat->distance }}">
  <p class="tab-note">Peta rute {{ $cat->distance }} akan tampil di sini setelah file GPX diunggah.</p>
@endif
</div>

@push('scripts')
@if($cat->route_points)
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
@if(config('services.google_maps.key'))
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}"></script>
<script src="https://unpkg.com/leaflet.gridlayer.googlemutant@0.14.1/dist/Leaflet.GoogleMutant.js"></script>
@endif
@endif
@verbatim
<script>
  (function(){
    const el = document.getElementById('course-map');
    if(el && window.L){
      let pts = [];
      try { pts = JSON.parse(el.dataset.points || '[]'); } catch(e){}
      if(pts.length){
        // layar sentuh: geser satu jari = scroll halaman; jelajah peta lewat mode layar penuh
        const isTouch = window.matchMedia('(pointer:coarse)').matches;
        const courseRoot = el.closest('.course');
        if(isTouch && courseRoot) courseRoot.classList.add('is-touch');
        const map = L.map(el, {scrollWheelZoom:false, attributionControl:false, zoomControl:false, dragging:!isTouch, tap:false});
        window._gtrMap = map;
        const routeColor = el.dataset.color || '#E53935';

        // ---- Basemaps: terrain/topo first — this is a trail race ----
        const hasGoogle = !!(window.google && window.google.maps && L.gridLayer.googleMutant);
        const trailStyle = [
          {featureType:'poi', stylers:[{visibility:'off'}]},
          {featureType:'poi.park', elementType:'geometry', stylers:[{visibility:'on'}]},
          {featureType:'transit', stylers:[{visibility:'off'}]},
          {featureType:'road', elementType:'labels.icon', stylers:[{visibility:'off'}]},
          {featureType:'road', elementType:'geometry', stylers:[{saturation:-100},{lightness:25}]},
          {featureType:'road', elementType:'labels.text.fill', stylers:[{color:'#6b6b6b'}]},
          {featureType:'administrative', elementType:'labels.text.fill', stylers:[{color:'#4a4a4a'}]},
          {featureType:'water', elementType:'geometry', stylers:[{color:'#9fcbe0'}]}
        ];
        const topo = () => L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {maxZoom:17, maxNativeZoom:17});
        const layers = hasGoogle ? [
          ['Satelit', L.gridLayer.googleMutant({type:'hybrid', maxZoom:20, styles:[
            {featureType:'poi', stylers:[{visibility:'off'}]},
            {featureType:'transit', stylers:[{visibility:'off'}]},
            {featureType:'road', elementType:'labels.icon', stylers:[{visibility:'off'}]}
          ]})],
          ['Terrain', L.gridLayer.googleMutant({type:'terrain', styles:trailStyle, maxZoom:20})],
          ['Topo', topo()]
        ] : [
          ['Satelit', L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {maxZoom:19})],
          ['Topo', topo()],
          ['Light', L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {maxZoom:19, subdomains:'abcd'})]
        ];
        let activeBase = layers[0][1].addTo(map);
        const layerBox = document.getElementById('map-layers');
        layers.forEach(([name, layer], i) => {
          const b = document.createElement('button');
          b.type = 'button'; b.textContent = name; if(i === 0) b.className = 'on';
          b.addEventListener('click', () => {
            if(activeBase === layer) return;
            map.removeLayer(activeBase); activeBase = layer.addTo(map); activeBase.bringToBack();
            layerBox.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
          });
          layerBox.appendChild(b);
        });

        // cumulative distance (km) along the route
        const toRad = d => d * Math.PI / 180, R = 6371;
        const cum = [0];
        for(let i=1;i<pts.length;i++){
          const a1=pts[i-1], a2=pts[i];
          const dLa=toRad(a2[0]-a1[0]), dLo=toRad(a2[1]-a1[1]);
          const h=Math.sin(dLa/2)**2 + Math.cos(toRad(a1[0]))*Math.cos(toRad(a2[0]))*Math.sin(dLo/2)**2;
          cum[i]=cum[i-1] + 2*R*Math.asin(Math.min(1, Math.sqrt(h)));
        }
        const total = cum[cum.length-1] || 1;
        function kmToLatLng(km){
          km = Math.max(0, Math.min(total, km));
          let j=1; while(j < cum.length && cum[j] < km) j++;
          if(j >= cum.length) j = cum.length - 1;
          const seg = cum[j]-cum[j-1];
          const t = seg ? (km - cum[j-1]) / seg : 0;
          return [pts[j-1][0] + (pts[j][0]-pts[j-1][0])*t, pts[j-1][1] + (pts[j][1]-pts[j-1][1])*t];
        }
        // elevation lookup (profile km is rescaled onto the drawn route)
        let rEp = [];
        try { rEp = JSON.parse(el.dataset.ep || '[]'); } catch(e){}
        const epMax = rEp.length ? rEp[rEp.length-1][0] : total;
        const kmEl = document.querySelector('[data-route-km]');
        if(kmEl) kmEl.textContent = (Math.round(epMax*10)/10).toFixed(1);
        function eleOnRoute(km){
          if(!rEp.length) return 0;
          const k = km / total * epMax;
          for(let i=1;i<rEp.length;i++){
            if(rEp[i][0] >= k){
              const k0=rEp[i-1][0], k1=rEp[i][0], tt=(k1-k0)?(k-k0)/(k1-k0):0;
              return rEp[i-1][1] + (rEp[i][1]-rEp[i-1][1])*tt;
            }
          }
          return rEp[rEp.length-1][1];
        }

        // ---- Route: casing + line ----
        const glow = L.polyline(pts, {color:'#000', weight:15, opacity:.28, lineJoin:'round', lineCap:'round', interactive:false}).addTo(map);
        const casing = L.polyline(pts, {color:'#101010', weight:11, opacity:.6, lineJoin:'round', lineCap:'round'}).addTo(map);
        const solid = L.layerGroup([
          L.polyline(pts, {color:'#fff', weight:8.5, opacity:1, lineJoin:'round', lineCap:'round'}),
          L.polyline(pts, {color:routeColor, weight:5.5, opacity:1, lineJoin:'round', lineCap:'round'})
        ]).addTo(map);

        // gradient-coloured route (by grade, ~200 m window)
        const gradeColor = g => g < -3 ? '#2563EB' : g < 3 ? '#22C55E' : g < 8 ? '#FACC15' : g < 15 ? '#F97316' : '#DC2626';
        const graded = L.layerGroup();
        if(rEp.length){
          let run = [pts[0]], runColor = null;
          for(let i=1;i<pts.length;i++){
            const mid = (cum[i-1] + cum[i]) / 2;
            const a = Math.max(0, mid - .1), b = Math.min(total, mid + .1);
            const g = b - a > .001 ? (eleOnRoute(b) - eleOnRoute(a)) / ((b - a) * 1000) * 100 : 0;
            const c = gradeColor(g);
            if(runColor && c !== runColor){
              graded.addLayer(L.polyline(run, {color:runColor, weight:6.5, opacity:1, lineJoin:'round', lineCap:'round'}));
              run = [pts[i-1]];
            }
            runColor = c; run.push(pts[i]);
          }
          graded.addLayer(L.polyline(run, {color:runColor, weight:6.5, opacity:1, lineJoin:'round', lineCap:'round'}));
        }
        const modeBox = document.getElementById('map-mode');
        const legend = document.getElementById('grade-legend');
        if(modeBox){
          modeBox.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
            const grade = b.dataset.mode === 'grade';
            modeBox.querySelectorAll('button').forEach(x => x.classList.toggle('on', x === b));
            if(grade){ map.removeLayer(solid); graded.addTo(map); casing.setStyle({color:'#fff', weight:11, opacity:.95}); }
            else { map.removeLayer(graded); solid.addTo(map); casing.setStyle({color:'#101010', weight:11, opacity:.6}); }
            legend && legend.classList.toggle('on', grade);
          }));
        }

        // keep the route clear of the HUD (top-left), tools (right) and docked profile
        const fitRoute = () => {
          const desk = window.matchMedia('(min-width:820px)').matches;
          map.fitBounds(casing.getBounds(), desk
            ? {paddingTopLeft:[60, 60], paddingBottomRight:[60, 60]}
            : {paddingTopLeft:[20, 96], paddingBottomRight:[64, 56]});
        };
        fitRoute();

        // ---- KM markers (density follows zoom) ----
        const kmLayer = L.layerGroup().addTo(map);
        function drawKm(){
          kmLayer.clearLayers();
          const z = map.getZoom();
          const step = z >= 15 ? 1 : z >= 14 ? 2 : 5;
          for(let k = step; k < total - .3; k += step){
            kmLayer.addLayer(L.marker(kmToLatLng(k), {
              icon: L.divIcon({className:'mk', iconSize:null, html:'<div class="kmm">' + k + '</div>'}),
              interactive:false, keyboard:false
            }));
          }
        }
        map.on('zoomend', drawKm); drawKm();


        // ================= Marker gaya poster =================
        // pin kecil di rute + keterangan teks tanpa kotak yang mencari tempat kosong
        let ws = [];
        try { ws = JSON.parse(el.dataset.ws || '[]'); } catch(e){}
        let pois = [];
        try { pois = JSON.parse(el.dataset.pois || '[]'); } catch(e){}
        const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const PT_COLOR = {ws:'#2EA6E6', cp:'#F97316'};
        const pinIco = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
        const clockIco = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 14"/></svg>';
        const fmtKm = v => (Math.round(v * 10) / 10).toString();
        const kmTxt = v => fmtKm(v).replace('.', ',');
        const eleKm = km => Math.round(eleOnRoute(km / epMax * total));
        const FAC = [
          ['🥤', /air|mineral|isotonik|minum|teh|kopi|cola|elektrolit/i],
          ['🍌', /buah|pisang|semangka|jeruk|rebus|makan|roti|gula|kurma|snack|kue|nasi/i],
          ['⛑️', /medis|medic|p3k|first aid|ambulan|salep|spray|obat/i],
          ['🚻', /toilet|wc|kamar mandi/i],
        ];
        const facOf = p => FAC.filter(f => (p.facilities || []).some(x => f[1].test(x))).map(f => f[0]);
        function popupHtml(p){
          let h = '<div class="pp" style="--pc:' + PT_COLOR[p.type] + '">'
            + '<div class="pp-h"><span class="pp-code">' + esc(p.code) + '</span><span class="pp-km">KM ' + fmtKm(p.km) + '</span></div>'
            + '<div class="pp-t">' + esc(p.title) + '</div>';
          if(p.location) h += '<div class="pp-r">' + pinIco + '<span>' + esc(p.location) + '</span></div>';
          if(p.cutoff) h += '<div class="pp-r cut">' + clockIco + '<span>Cut-off ' + esc(p.cutoff) + ' WITA</span></div>';
          if(p.facilities && p.facilities.length) h += '<div class="pp-fac">' + p.facilities.map(f => '<span>' + esc(f) + '</span>').join('') + '</div>';
          if(p.notes) h += '<div class="pp-n">' + esc(p.notes) + '</div>';
          return h + '</div>';
        }

        const items = [];
        const pin = (ll, label, color, z, extra) => L.marker(ll, {
          icon: L.divIcon({className:'mk', iconSize:null, html:'<div class="pin2' + (extra || '') + '" style="--c:' + color + '"><span class="ph">' + label + '</span><i class="pt"></i></div>'}),
          zIndexOffset: z, keyboard:false, riseOnHover:true
        }).addTo(map);
        const first = pts[0], last = pts[pts.length-1], sameSF = map.distance(first, last) < 150;
        items.push({km:0, prio:0, marker: pin(first, 'START', '#16A34A', 1000, ' sm'), head: sameSF ? 'START · FINISH' : 'START', headCls:'go',
          lines:[eleKm(0) + ' M  |  ' + (planStart() || 'KM 0')]});
        if(!sameSF) items.push({km:epMax, prio:0, marker: pin(last, '🏁', '#101010', 1000), head:'FINISH', headCls:'fin', lines:[eleKm(epMax) + ' M  |  ' + kmTxt(epMax) + ' KM']});
        function planStart(){ const pl = document.getElementById('race-plan'); if(!pl || pl.dataset.start === '') return null; const m = Number(pl.dataset.start); return String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0') + ' WITA'; }

        const ptMarkers = ws.map(p => {
          const m = pin(kmToLatLng(Number(p.km) / epMax * total), esc(p.code), PT_COLOR[p.type], p.type === 'cp' ? 860 : 850)
            .bindPopup(popupHtml(p), {className:'pt-popup', closeButton:false, offset:[0,-38], autoPanPadding:[30,30]});
          items.push({km:Number(p.km), prio:1, marker:m, type:p.type, code:p.code,
            title: p.name || p.location || (p.type === 'cp' ? 'Check Point' : 'Water Station'),
            lines:[eleKm(Number(p.km)) + ' M  |  ' + kmTxt(p.km) + ' KM'], fac:facOf(p), cot:p.cutoff});
          return m;
        });
        if(rEp.length){
          let top = rEp[0];
          rEp.forEach(p => { if(p[1] > top[1]) top = p; });
          const sm = pin(kmToLatLng(top[0] / epMax * total), '▲', '#101010', 800).bindTooltip('Titik tertinggi · KM ' + top[0].toFixed(1), {direction:'top', className:'ws-tip', offset:[0,-40]});
          items.push({km:top[0], prio:2, marker:sm, title:'Titik tertinggi', lines:[Math.round(top[1]) + ' M  |  ' + kmTxt(top[0]) + ' KM']});
        }
        const hzLine = L.layerGroup().addTo(map);
        const poiMarkers = pois.map(p => {
          if(p.km_end){
            const seg = [];
            for(let k = p.km; k < p.km_end; k += .02) seg.push(kmToLatLng(k / epMax * total));
            seg.push(kmToLatLng(p.km_end / epMax * total));
            hzLine.addLayer(L.polyline(seg, {color: p.hazard ? '#FACC15' : '#fff', weight:18, opacity:.45, lineCap:'round', interactive:false}));
          }
          const range = 'KM ' + fmtKm(p.km) + (p.km_end ? '–' + fmtKm(p.km_end) : '');
          const m = L.marker(kmToLatLng(p.km / epMax * total), {
            icon: L.divIcon({className:'mk', iconSize:null, html:'<div class="poim' + (p.hazard ? ' hz' : '') + '">' + esc(p.icon) + '</div>'}),
            zIndexOffset: 840
          }).addTo(map).bindPopup(
            '<div class="pp" style="--pc:' + (p.hazard ? '#F97316' : '#374151') + '"><div class="pp-h"><span class="pp-code">' + esc(p.icon + ' ' + p.label) + '</span><span class="pp-km">' + range + '</span></div>'
            + '<div class="pp-t">' + esc(p.title) + '</div>' + (p.note ? '<div class="pp-n">' + esc(p.note) + '</div>' : '') + '</div>',
            {className:'pt-popup', closeButton:false, offset:[0,-14], autoPanPadding:[30,30]});
          items.push({km:Number(p.km), prio:3, marker:m, poi:true, title:p.title, lines:[range.replace('.', ',')]});
          return m;
        });
        glow.bringToBack(); hzLine.eachLayer(l => l.bringToBack());

        // label teks (satu marker per label; posisi dihitung di layout())
        items.forEach(it => {
          let h = '';
          if(it.head) h += '<b class="lh ' + it.headCls + '">' + esc(it.head) + '</b>';
          if(it.title) h += '<b class="lt">' + esc(it.title) + '</b>';
          it.lines.forEach(l => { h += '<span class="ll">' + esc(l) + '</span>'; });
          if((it.fac && it.fac.length) || it.cot) h += '<span class="lf">' + (it.fac || []).map(f => '<i>' + f + '</i>').join('') + (it.cot ? '<em>COT ' + esc(it.cot) + '</em>' : '') + '</span>';
          it.html = h;
        });
        // pos di lokasi & nama sama → satu label
        const byTitle = {};
        items.filter(it => it.type && it.title).forEach(it => (byTitle[it.title] = byTitle[it.title] || []).push(it));
        Object.values(byTitle).filter(a => a.length > 1).forEach(a => {
          a.sort((x, y) => x.km - y.km);
          const near = a.filter(it => map.distance(it.marker.getLatLng(), a[0].marker.getLatLng()) < 120);
          if(near.length < 2) return;
          const lead = near[0], facs = [];
          near.forEach(it => (it.fac || []).forEach(f => { if(!facs.includes(f)) facs.push(f); }));
          const cot = near.map(it => it.cot).filter(Boolean)[0];
          lead.html = '<b class="lt">' + esc(lead.title) + '</b>' + near.map(it => '<span class="ll">' + esc(it.code + ' · ' + it.lines[0]) + '</span>').join('')
            + ((facs.length || cot) ? '<span class="lf">' + facs.map(f => '<i>' + f + '</i>').join('') + (cot ? '<em>COT ' + esc(cot) + '</em>' : '') + '</span>' : '');
          lead.group = near;
          near.slice(1).forEach(it => { it.noLabel = true; });
        });
        items.filter(it => !it.noLabel).forEach(it => {
          it.label = L.marker(it.marker.getLatLng(), {
            icon: L.divIcon({className:'mk lblm', iconSize:null, html:'<div class="lbl2">' + it.html + '</div>'}),
            zIndexOffset: 700, keyboard:false
          }).addTo(map).on('click', () => it.marker.openPopup ? it.marker.openPopup() : null);
        });

        // panah arah (statis) di samping rute
        const arrowLayer = L.layerGroup().addTo(map);
        const leaderLayer = L.layerGroup().addTo(map);

        function layout(){
          const size = map.getSize(), cpt = p => map.layerPointToContainerPoint(p);
          // area overlay UI yang harus dihindari label (koordinat container)
          const stageR = el.getBoundingClientRect();
          const avoid = [...el.closest('.map-box').querySelectorAll('.map-ui')].filter(n => !n.hidden && n.offsetParent).map(n => {
            const r = n.getBoundingClientRect(); return {x:r.left - stageR.left, y:r.top - stageR.top, w:r.width, h:r.height};
          });
          // grid rute untuk menghindari teks menutupi jalur
          const cell = 14, occ = new Set();
          const lp = pts.map(p => cpt(map.latLngToLayerPoint(p)));
          for(let i = 1; i < lp.length; i++){
            const a = lp[i-1], b = lp[i], n = Math.max(1, Math.ceil(a.distanceTo(b) / 6));
            for(let k = 0; k <= n; k++) occ.add(Math.floor((a.x + (b.x - a.x) * k / n) / cell) + ',' + Math.floor((a.y + (b.y - a.y) * k / n) / cell));
          }
          const routeHits = r => { let h = 0; for(let gx = Math.floor(r.x / cell); gx <= Math.floor((r.x + r.w) / cell); gx++) for(let gy = Math.floor(r.y / cell); gy <= Math.floor((r.y + r.h) / cell); gy++) if(occ.has(gx + ',' + gy)) h++; return h; };
          const hit = (a, b) => a.x < b.x + b.w && a.x + a.w > b.x && a.y < b.y + b.h && a.y + a.h > b.y;

          // 1) kepala pin: sebar bila bertumpuk
          leaderLayer.clearLayers();
          const pinsIt = items.filter(it => !it.poi);
          pinsIt.forEach(it => { it.p = cpt(map.latLngToLayerPoint(it.marker.getLatLng())); it.hx = it.p.x; it.hy = it.p.y - 22; });
          items.filter(it => it.poi).forEach(it => { it.p = cpt(map.latLngToLayerPoint(it.marker.getLatLng())); it.hx = it.p.x; it.hy = it.p.y; });
          const groups = [];
          pinsIt.forEach(it => { const g = groups.find(g => g.some(o => Math.hypot(o.hx - it.hx, o.hy - it.hy) < 30)); g ? g.push(it) : groups.push([it]); });
          groups.forEach(g => {
            const stem = g.length > 1;
            g.sort((a, b) => a.km - b.km);
            const mx = g.reduce((s, i) => s + i.p.x, 0) / g.length, top = Math.min(...g.map(i => i.p.y)) - 52;
            g.forEach((it, i) => {
              const e = it.marker.getElement(); if(!e) return;
              const head = e.firstElementChild;
              head.classList.toggle('stem', stem);
              if(stem){
                it.hx = mx + (i - (g.length - 1) / 2) * 34; it.hy = top;
                e.style.marginLeft = (it.hx - it.p.x) + 'px'; e.style.marginTop = (it.hy - it.p.y) + 'px';
                const ll1 = map.containerPointToLatLng(it.p), ll2 = map.containerPointToLatLng(L.point(it.hx, it.hy + 13));
                leaderLayer.addLayer(L.polyline([ll1, ll2], {color:'#000', weight:5, opacity:.45, interactive:false}));
                leaderLayer.addLayer(L.polyline([ll1, ll2], {color:'#fff', weight:2.5, opacity:1, interactive:false}));
                leaderLayer.addLayer(L.circleMarker(ll1, {radius:4, color:'#fff', weight:2, fillColor:it.marker.options.icon.options.html.match(/--c:([^"]+)"/)[1], fillOpacity:1, interactive:false}));
              } else { e.style.marginLeft = ''; e.style.marginTop = ''; }
            });
          });

          // 2) label: coba posisi di sekitar kepala pin, pilih yang paling kosong
          const blocked = avoid.slice();
          items.forEach(it => blocked.push(it.poi ? {x:it.hx - 15, y:it.hy - 15, w:30, h:30} : {x:it.hx - 15, y:it.hy - 15, w:30, h:30}));
          const placed = [];
          items.filter(it => it.label).sort((a, b) => a.prio - b.prio || a.km - b.km).forEach(it => {
            const e = it.label.getElement(); if(!e) return;
            const box = e.firstElementChild;
            box.style.display = ''; e.style.marginLeft = '0px'; e.style.marginTop = '0px';
            const w = box.offsetWidth, h = box.offsetHeight;
            const heads = it.group ? it.group.map(g => [g.hx, g.hy]) : [[it.hx, it.hy]];
            const hx = heads.reduce((s, q) => s + q[0], 0) / heads.length, hy = Math.min(...heads.map(q => q[1]));
            const gw = heads.length > 1 ? (Math.max(...heads.map(q => q[0])) - Math.min(...heads.map(q => q[0]))) / 2 : 0;
            let best = null;
            [26, 52, 84, 120, 160].forEach(d => {
              for(let a = 0; a < 16; a++){
                const ang = a / 16 * Math.PI * 2, right = Math.cos(ang) >= -0.01;
                const px = hx + Math.cos(ang) * (d + gw), py = hy + Math.sin(ang) * d * .8;
                const r = {x: right ? px : px - w, y: py - h / 2, w, h};
                if(r.x < 6 || r.y < 6 || r.x + w > size.x - 6 || r.y + h > size.y - 6) continue;
                const pad = {x:r.x - 4, y:r.y - 3, w:r.w + 8, h:r.h + 6};
                if(blocked.some(b => hit(b, pad)) || placed.some(b => hit(b, pad))) continue;
                const score = routeHits(r) * 30 + d + (Math.abs(Math.sin(ang)) > .9 ? 20 : 0);
                if(!best || score < best.score) best = {r, score, right};
              }
            });
            if(!best){ box.style.display = 'none'; return; }   // tidak ada tempat → sembunyikan teks, pin tetap
            placed.push(best.r);
            box.classList.toggle('lt-l', !best.right);
            // posisi relatif terhadap titik marker label (= lokasi pos)
            e.style.marginLeft = (best.r.x - it.p.x) + 'px'; e.style.marginTop = (best.r.y - it.p.y) + 'px';
            const tx = best.right ? best.r.x - 3 : best.r.x + w + 3, ty = Math.max(best.r.y + 8, Math.min(best.r.y + h - 8, hy));
            if(Math.hypot(tx - hx, ty - hy) > 24)
              leaderLayer.addLayer(L.polyline([map.containerPointToLatLng(L.point(hx, hy)), map.containerPointToLatLng(L.point(tx, ty))], {color:'#fff', weight:2, opacity:.95, dashArray:'2 5', interactive:false}));
          });

          // 3) badge KM yang tertutup → sembunyikan
          kmLayer.eachLayer(k => {
            const kp = cpt(map.latLngToLayerPoint(k.getLatLng())), ke = k.getElement();
            const r = {x:kp.x - 10, y:kp.y - 10, w:20, h:20};
            if(ke) ke.style.opacity = (placed.some(b => hit(b, r)) || items.some(it => Math.hypot(it.hx - kp.x, it.hy - kp.y) < 22 || Math.hypot(it.p.x - kp.x, it.p.y - kp.y) < 16)) ? '0' : '';
          });

          // 4) panah arah kuning di samping rute
          arrowLayer.clearLayers();
          const gap = Math.max(.5, Math.round(epMax / 18 * 10) / 10);
          const arrowSvg = '<svg viewBox="0 0 24 24"><path d="M5 3.5 L20 12 L5 20.5 L9 12 Z" fill="#FACC15" stroke="#111" stroke-width="2.2" stroke-linejoin="round"/></svg>';
          for(let k = gap / 2; k < epMax - .2; k += gap){
            const a = map.latLngToLayerPoint(kmToLatLng(Math.max(0, k - .05) / epMax * total)), b = map.latLngToLayerPoint(kmToLatLng(Math.min(epMax, k + .05) / epMax * total));
            const ang = Math.atan2(b.y - a.y, b.x - a.x), mid = map.latLngToLayerPoint(kmToLatLng(k / epMax * total));
            const off = L.point(mid.x + Math.cos(ang + Math.PI / 2) * 19, mid.y + Math.sin(ang + Math.PI / 2) * 19);
            const oc = cpt(off);
            if(placed.some(r => hit(r, {x:oc.x - 11, y:oc.y - 11, w:22, h:22}))) continue;
            arrowLayer.addLayer(L.marker(map.layerPointToLatLng(off), {interactive:false, keyboard:false,
              icon: L.divIcon({className:'mk', iconSize:null, html:'<div class="arw2" style="transform:translate(-50%,-50%) rotate(' + (ang * 180 / Math.PI).toFixed(1) + 'deg)">' + arrowSvg + '</div>'})}));
          }
        }
        map.on('moveend resize', layout);
        setTimeout(layout, 80);
        document.querySelectorAll('.poi-item').forEach(item => item.addEventListener('click', () => {
          const m = poiMarkers[Number(item.dataset.poi)];
          if(!m) return;
          el.closest('.map-stage').scrollIntoView({behavior:'smooth', block:'center'});
          map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 15), {duration:.8});
          map.once('moveend', () => m.openPopup());
        }));

        // kartu pos → fokus ke marker di peta
        document.querySelectorAll('.pt-card').forEach(card => card.addEventListener('click', () => {
          const m = ptMarkers[Number(card.dataset.pt)];
          if(!m) return;
          document.querySelectorAll('.pt-card').forEach(c => c.classList.toggle('on', c === card));
          el.closest('.map-stage').scrollIntoView({behavior:'smooth', block:'center'});
          map.flyTo(m.getLatLng(), Math.max(map.getZoom(), 15), {duration:.8});
          map.once('moveend', () => m.openPopup());
        }));

        // ---- Lokasi saya (GPS) ----
        const gpsPanel = el.closest('.map-stage').querySelector('[data-gps-panel]');
        const planEl = document.getElementById('race-plan');
        let gpsWatch = null, gpsDot = null, gpsAcc = null, gpsFirst = true;
        function nearestOnRoute(ll){
          const p = map.latLngToLayerPoint(ll);
          let best = {d: Infinity, k: 0, ll: null};
          for(let i = 1; i < pts.length; i++){
            const a = map.latLngToLayerPoint(pts[i-1]), b = map.latLngToLayerPoint(pts[i]);
            const dx = b.x - a.x, dy = b.y - a.y, len = dx*dx + dy*dy;
            const t = len ? Math.max(0, Math.min(1, ((p.x-a.x)*dx + (p.y-a.y)*dy) / len)) : 0;
            const d = Math.hypot(p.x - (a.x + t*dx), p.y - (a.y + t*dy));
            if(d < best.d) best = {d, k: cum[i-1] + (cum[i]-cum[i-1]) * t, ll: map.layerPointToLatLng(L.point(a.x + t*dx, a.y + t*dy))};
          }
          return {km: best.k / total * epMax, meters: map.distance(ll, best.ll)};
        }
        function gpsUpdate(pos){
          const ll = L.latLng(pos.coords.latitude, pos.coords.longitude);
          if(!gpsDot){
            gpsDot = L.marker(ll, {icon: L.divIcon({className:'', iconSize:null, html:'<div class="gps-dot"></div>'}), zIndexOffset:2000, interactive:false}).addTo(map);
            gpsAcc = L.circle(ll, {radius: pos.coords.accuracy, color:'#2563EB', weight:1, fillOpacity:.08, interactive:false}).addTo(map);
          } else { gpsDot.setLatLng(ll); gpsAcc.setLatLng(ll).setRadius(pos.coords.accuracy); }
          if(gpsFirst){ map.setView(ll, Math.max(map.getZoom(), 15)); gpsFirst = false; }
          const n = nearestOnRoute(ll), off = n.meters > 80;
          const next = ws.map(w => ({code: w.code, km: Number(w.km)})).filter(w => w.km > n.km + .02).sort((a, b) => a.km - b.km)[0];
          let html = '<div class="g1' + (off ? ' off' : '') + '">' + (off ? '⚠ ' + Math.round(n.meters) + ' m dari rute' : 'KM ' + n.km.toFixed(1) + ' · di rute') + '</div><div class="g2">';
          if(off) html += '<span>Titik rute terdekat: <b>KM ' + n.km.toFixed(1) + '</b></span>';
          if(next) html += '<span>' + esc(next.code) + ' · <b>' + (next.km - n.km).toFixed(1) + ' km</b> lagi</span>';
          html += '<span>Finish · <b>' + Math.max(0, epMax - n.km).toFixed(1) + ' km</b> lagi</span>';
          // sisa waktu ke COT (hanya dalam jendela race: start ≤ sekarang ≤ COT)
          if(planEl && planEl.dataset.start !== '' && planEl.dataset.cot !== ''){
            const now = new Date(), nowMin = now.getHours() * 60 + now.getMinutes();
            const s = Number(planEl.dataset.start), cot = s + Number(planEl.dataset.cot);
            if(nowMin >= s && nowMin <= cot){ const left = cot - nowMin; html += '<span>Sisa ke COT · <b>' + Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0') + '</b></span>'; }
          }
          gpsPanel.innerHTML = html + '<span style="opacity:.6">Akurasi ±' + Math.round(pos.coords.accuracy) + ' m</span></div>';
          gpsPanel.hidden = false;
        }
        function gpsError(err){
          gpsPanel.hidden = false;
          gpsPanel.innerHTML = '<div class="g1 off">Lokasi tidak tersedia</div><div class="g2"><span>' + (err && err.code === 1 ? 'Izinkan akses lokasi di browser.' : 'Pastikan GPS aktif & coba lagi.') + '</span></div>';
        }
        function toggleGps(btn){
          if(gpsWatch !== null){
            navigator.geolocation.clearWatch(gpsWatch); gpsWatch = null;
            if(gpsDot){ map.removeLayer(gpsDot); map.removeLayer(gpsAcc); gpsDot = gpsAcc = null; }
            gpsPanel.hidden = true; btn.classList.remove('on'); gpsFirst = true;
            return;
          }
          if(!navigator.geolocation || !window.isSecureContext){ gpsError({code: 0}); return; }
          btn.classList.add('on');
          gpsWatch = navigator.geolocation.watchPosition(gpsUpdate, gpsError, {enableHighAccuracy: true, maximumAge: 5000, timeout: 20000});
        }

        // ---- Simpan untuk offline (app) ----
        const offRow = document.querySelector('[data-offline]');
        if(offRow && 'caches' in window && 'serviceWorker' in navigator){
          offRow.hidden = false;
          const offBtn = offRow.querySelector('[data-offline-btn]'), offMsg = offRow.querySelector('[data-offline-msg]');
          offBtn.addEventListener('click', async () => {
            offBtn.disabled = true;
            const b = casing.getBounds().pad(.15), urls = [], subs = ['a', 'b', 'c'];
            for(let z = 13; z <= 16; z++){
              const p1 = map.project(b.getNorthWest(), z).divideBy(256).floor(), p2 = map.project(b.getSouthEast(), z).divideBy(256).floor();
              for(let x = p1.x; x <= p2.x; x++) for(let y = p1.y; y <= p2.y; y++)
                urls.push('https://' + subs[Math.abs(x + y) % 3] + '.tile.opentopomap.org/' + z + '/' + x + '/' + y + '.png');
            }
            try {
              const pages = await caches.open('runger-pages-v1');
              await pages.put(location.pathname, await fetch(location.pathname, {credentials: 'same-origin'}));
              const tiles = await caches.open('runger-tiles-v1');
              let done = 0;
              for(const u of urls){
                if(!(await tiles.match(u))){ try { await tiles.put(u, await fetch(u, {mode: 'no-cors'})); } catch(e){} }
                done++; offBtn.textContent = 'Menyimpan ' + Math.round(done / urls.length * 100) + '%';
              }
              offBtn.textContent = '✓ Tersimpan'; offBtn.classList.add('done');
              offMsg.textContent = 'Saat tanpa sinyal, buka Runger App → kategori ini. Peta memakai layer Topo.';
            } catch(e){
              offBtn.disabled = false; offBtn.textContent = 'Coba lagi';
              offMsg.textContent = 'Gagal menyimpan — pastikan ada sinyal lalu coba lagi.';
            }
          });
        }

        // ---- Tools ----
        const stage = el.closest('.map-stage');
        document.querySelectorAll('.map-tools button').forEach(b => b.addEventListener('click', () => {
          const act = b.dataset.act;
          if(act === 'in') map.zoomIn();
          else if(act === 'out') map.zoomOut();
          else if(act === 'fit') fitRoute();
          else if(act === 'fs') toggleFs();
          else if(act === 'gps') toggleGps(b);
        }));
        const hintBtn = stage.querySelector('.map-hint');
        if(hintBtn) hintBtn.addEventListener('click', () => toggleFs());
        function afterFs(on){
          if(isTouch){ on ? map.dragging.enable() : map.dragging.disable(); }
          document.documentElement.style.overflow = stage.classList.contains('is-fs') ? 'hidden' : '';
          setTimeout(() => { map.invalidateSize(); fitRoute(); }, 120);
        }
        function toggleFs(){
          // HP & iOS: layar penuh versi CSS (Fullscreen API tidak tersedia di iPhone)
          if(isTouch || !stage.requestFullscreen){
            const on = !stage.classList.contains('is-fs');
            stage.classList.toggle('is-fs', on);
            afterFs(on);
          } else if(document.fullscreenElement){
            document.exitFullscreen();
          } else {
            stage.requestFullscreen();
          }
        }
        document.addEventListener('fullscreenchange', () => afterFs(!!document.fullscreenElement));
        document.addEventListener('keydown', e => { if(e.key === 'Escape' && stage.classList.contains('is-fs')) toggleFs(); });

        // ---- Elevation profile <-> map scrubber ----
        const chart = document.querySelector('.elev-chart');
        if(chart){
          let ep = [];
          try { ep = JSON.parse(chart.dataset.ep || '[]'); } catch(e){}
          const maxKm = parseFloat(chart.dataset.maxkm) || total;
          const minKm = parseFloat(chart.dataset.minkm) || 0;
          const minEle = parseFloat(chart.dataset.minele) || 0;
          const maxEle = parseFloat(chart.dataset.maxele) || (minEle + 1);
          const cursor = chart.querySelector('.elev-cursor');
          const cdot = chart.querySelector('.cdot');
          const tip = chart.querySelector('.elev-tip');
          const H = 220, padT = 16, padB = 22; // must match the SVG viewBox
          const hover = L.circleMarker(pts[0], {radius:7, color:'#fff', weight:3, fillColor:'#101010', fillOpacity:1});
          function eleAtKm(km){
            if(!ep.length) return minEle;
            for(let i=1;i<ep.length;i++){
              if(ep[i][0] >= km){
                const k0=ep[i-1][0], k1=ep[i][0], e0=ep[i-1][1], e1=ep[i][1];
                const t=(k1-k0)?(km-k0)/(k1-k0):0;
                return e0 + (e1-e0)*t;
              }
            }
            return ep[ep.length-1][1];
          }
          // cumulative ascent / descent along the profile
          const gainAt = [0], lossAt = [0];
          for(let i=1;i<ep.length;i++){
            const d = ep[i][1] - ep[i-1][1];
            gainAt[i] = gainAt[i-1] + Math.max(0, d);
            lossAt[i] = lossAt[i-1] + Math.max(0, -d);
          }
          function climbAtKm(km){
            if(!ep.length) return [0, 0];
            let i = 1; while(i < ep.length && ep[i][0] < km) i++;
            if(i >= ep.length) return [gainAt[ep.length-1], lossAt[ep.length-1]];
            const k0=ep[i-1][0], k1=ep[i][0], t=(k1-k0)?(km-k0)/(k1-k0):0;
            const d = (ep[i][1]-ep[i-1][1])*t;
            return [gainAt[i-1] + Math.max(0, d), lossAt[i-1] + Math.max(0, -d)];
          }
          // grade (%) over a ~200 m window around km
          function gradeAtKm(km){
            const w = 0.1;
            const a = Math.max(minKm, km - w), b = Math.min(maxKm, km + w);
            if(b - a < 0.001) return 0;
            return (eleAtKm(b) - eleAtKm(a)) / ((b - a) * 1000) * 100;
          }
          const wsSorted = ws.map(w => ({km: Number(w.km), name: w.code})).sort((a, b) => a.km - b.km);
          const fmt = n => (Math.round(n * 10) / 10).toFixed(1);
          function move(clientX){
            const rect = chart.getBoundingClientRect();
            const frac = Math.min(1, Math.max(0, (clientX-rect.left)/rect.width));
            const km = minKm + frac*(maxKm-minKm);
            const ele = eleAtKm(km);
            const vy = H - padB - ((ele-minEle)/Math.max(1,(maxEle-minEle)))*(H-padT-padB);
            const topPct = vy/H*100;
            cursor.style.left = (frac*100)+'%'; cursor.style.display='block';
            cdot.style.top = topPct+'%';
            tip.style.left = (frac*100)+'%'; tip.style.top = topPct+'%'; tip.style.display='block';
            tip.classList.toggle('al', frac < .2);
            tip.classList.toggle('ar', frac > .8);

            const g = gradeAtKm(km);
            const gCls = g > 1.5 ? 'up' : (g < -1.5 ? 'dn' : 'flat');
            const gTxt = (g > 1.5 ? '↗ +' : (g < -1.5 ? '↘ ' : '→ ')) + Math.round(g) + '%';
            const [up, down] = climbAtKm(km);
            const next = wsSorted.find(w => w.km > km + 0.05);
            const nextTxt = next ? next.name + ' · ' + fmt(next.km - km) + ' km' : 'Finish · ' + fmt(Math.max(0, maxKm - km)) + ' km';
            tip.innerHTML =
              '<div class="tt-head"><span class="tt-km">KM ' + fmt(km) + '</span><span class="tt-ele">' + Math.round(ele) + ' mdpl</span></div>' +
              '<div class="tt-row"><span>Gradien</span><b class="' + gCls + '">' + gTxt + '</b></div>' +
              '<div class="tt-row"><span>Total naik / turun</span><b><span class="up">+' + Math.round(up) + '</span> / <span class="dn">−' + Math.round(down) + '</span> m</b></div>' +
              '<div class="tt-row"><span>Berikutnya</span><b>' + nextTxt + '</b></div>';
            cdot.style.background = gCls === 'up' ? '#E53935' : (gCls === 'dn' ? '#2EA6E6' : '#101010');
            hover.setLatLng(kmToLatLng(km / epMax * total));
            hover.setStyle({fillColor: cdot.style.background});
            if(!map.hasLayer(hover)) hover.addTo(map);
          }
          function hide(){ cursor.style.display='none'; if(!el.closest('.course--app')) tip.style.display='none'; if(map.hasLayer(hover)) map.removeLayer(hover); }
          chart.addEventListener('mousemove', e => move(e.clientX));
          chart.addEventListener('mouseleave', hide);
          chart.addEventListener('touchmove', e => { if(e.touches[0]) move(e.touches[0].clientX); }, {passive:true});
          chart.addEventListener('touchstart', e => { if(e.touches[0]) move(e.touches[0].clientX); }, {passive:true});
          // varian app: detail tampil di panel tetap di atas grafik (tidak tertutup jari)
          if(el.closest('.course--app')){
            tip.innerHTML = '';
            const pinsRow = chart.previousElementSibling && chart.previousElementSibling.classList.contains('elev-pins') ? chart.previousElementSibling : chart;
            chart.parentNode.insertBefore(tip, pinsRow);
          }
        }
      }
    }

    // ---- Segmen & estimasi waktu ----
    const plan = document.getElementById('race-plan');
    if(plan){
      let course = {};
      try { course = JSON.parse(plan.dataset.course || '{}'); } catch(e){}
      const segs = course.segments || [], stops = course.stops || [];
      const startMin = plan.dataset.start !== '' ? Number(plan.dataset.start) : null;
      const cotMin = plan.dataset.cot !== '' ? Number(plan.dataset.cot) : null;
      const effort = segs.reduce((s, x) => s + x.effort, 0) || 1;
      const range = plan.querySelector('[data-plan-range]');
      const listEl = plan.querySelector('[data-plan-list]');
      const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
      const COLOR = {start:'#22C55E', finish:'#101010', ws:'#2EA6E6', cp:'#F97316'};
      const hm = m => Math.floor(m / 60) + ':' + String(Math.round(m % 60)).padStart(2, '0');
      const clock = m => { m = ((Math.round(m) % 1440) + 1440) % 1440; return String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0'); };
      const toMin = s => { const r = /^(\d{1,2}):(\d{2})$/.exec(s || ''); return r ? Number(r[1]) * 60 + Number(r[2]) : null; };
      const tagCls = l => /berat/i.test(l) ? 'hard' : /menanjak|naik-turun/i.test(l) ? 'mid' : /turun/i.test(l) ? 'down' : '';

      const lo = Math.max(30, Math.round(effort * 4 / 5) * 5);
      const hi = cotMin ? Math.max(lo + 30, cotMin) : Math.round(effort * 12 / 5) * 5;
      range.min = lo; range.max = hi;
      const saved = plan.dataset.saved !== '' ? Number(plan.dataset.saved) : null;
      range.value = saved || Math.min(hi, Math.max(lo, Math.round((cotMin ? cotMin * .72 : effort * 7.5) / 5) * 5));
      const saveInput = plan.querySelector('[data-plan-input]'), saveBtn = plan.querySelector('[data-plan-save]');
      plan.querySelector('[data-plan-min]').textContent = hm(lo);
      plan.querySelector('[data-plan-max]').textContent = hm(hi) + (cotMin && hi === cotMin ? ' (COT)' : '');

      function cotBadge(eta, cut){
        if(cut == null || startMin == null) return '';
        const buf = cut - eta;
        const cls = buf >= 15 ? 'ok' : buf >= 0 ? 'warn' : 'bad';
        const txt = buf >= 0 ? 'COT ' + clock(cut) + ' · sisa ' + hm(buf) : 'Lewat COT ' + clock(cut);
        return '<span class="pl-cot ' + cls + '">' + txt + '</span>';
      }
      function render(){
        const T = Number(range.value);
        plan.querySelector('[data-plan-target]').textContent = hm(T);
        if(saveInput){
          saveInput.value = T;
          const same = saved === T && (!modeInput || modeInput.value === (plan.dataset.savedMode || 'pos'));
          saveBtn.classList.toggle('saved', same);
          saveBtn.textContent = same ? '✓ Race Plan tersimpan' : 'Simpan sebagai Race Plan';
        }
        const pace = T / (course.total_km || 1);
        plan.querySelector('[data-plan-pace]').textContent = Math.floor(pace) + ':' + String(Math.round((pace % 1) * 60)).padStart(2, '0') + ' /km';
        let elapsed = 0, html = '';
        stops.forEach((s, i) => {
          if(i > 0){
            const g = segs[i - 1], dur = T * g.effort / effort;
            elapsed += dur;
            html += '<div class="pl-seg"><div class="pl-seg-in">'
              + '<span><b>' + g.dist.toFixed(1) + '</b> km</span>'
              + '<span class="up">↗ ' + g.gain + ' m</span><span class="dn">↘ ' + g.loss + ' m</span>'
              + '<span>maks ' + g.max_grade + '%</span>'
              + '<span class="pl-tag ' + tagCls(g.label) + '">' + esc(g.label) + '</span>'
              + '<span class="pl-dur">± ' + hm(dur) + '</span>'
              + '</div></div>';
          }
          const eta = startMin != null ? startMin + elapsed : null;
          let cut = s.type === 'finish' ? (startMin != null && cotMin ? startMin + cotMin : null) : toMin(s.cutoff);
          if(cut != null && startMin != null && cut < startMin) cut += 1440;
          const name = s.type === 'start' || s.type === 'finish' ? s.title : (s.title || s.code);
          html += '<div class="pl-stop" style="--pc:' + COLOR[s.type] + '">'
            + '<span class="pl-badge">' + esc(s.code) + '</span>'
            + '<div><div class="pl-name">' + esc(name) + '</div><div class="pl-sub">KM ' + (Math.round(s.km * 10) / 10) + '</div></div>'
            + '<div class="pl-time">'
            + (eta != null ? '<div class="pl-eta">' + clock(eta) + '</div>' : '<div class="pl-eta">' + hm(elapsed) + '</div>')
            + (i > 0 && eta != null ? '<div class="pl-el">+' + hm(elapsed) + '</div>' : (i === 0 ? '<div class="pl-el">Gun start</div>' : ''))
            + cotBadge(eta, cut)
            + '</div></div>';
        });
        listEl.innerHTML = html;
        if(typeof renderSplits === 'function') renderSplits(T);
      }
      // ---- Split per KM ----
      const splits = course.splits || [];
      const splitBox = plan.querySelector('[data-plan-splits]');
      const splitRows = plan.querySelector('[data-plan-split-rows]');
      const splitEffort = splits.reduce((s, x) => s + x.effort, 0) || 1;
      const PT = {ws:'#2EA6E6', cp:'#F97316'};
      const gradeColor = g => g < -3 ? '#2563EB' : g < 3 ? '#22C55E' : g < 8 ? '#FACC15' : g < 15 ? '#F97316' : '#DC2626';
      const mmss = m => { const s = Math.round(m * 60); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
      function renderSplits(T){
        const paces = splits.map(s => (T * s.effort / splitEffort) / s.dist);
        const pMin = Math.min(...paces), pMax = Math.max(...paces);
        let elapsed = 0, html = '';
        splits.forEach((s, i) => {
          const dur = T * s.effort / splitEffort;
          elapsed += dur;
          const pace = paces[i];
          // KM naik-turun besar (net kecil) tetap ditandai berat
          const tone = s.gain >= 60 && s.loss >= 60 ? '#F97316' : gradeColor(s.grade);
          const w = pMax > pMin ? 18 + (pace - pMin) / (pMax - pMin) * 82 : 60;
          const pts = s.points.length ? '<div class="sp-pts">' + s.points.map(p => '<span style="background:' + PT[p.type] + '">' + esc(p.code) + '</span>').join('') + '</div>' : '';
          const hz = (s.hazards || []).length ? '<div class="sp-hz">' + s.hazards.map(h => '<span title="' + esc(h.title) + '">' + esc(h.icon + ' ' + h.title) + '</span>').join('') + '</div>' : '';
          const sign = s.grade > 0 ? '+' : '';
          html += '<div class="sp-row' + (s.points.length ? ' has-pt' : '') + '">'
            + '<div><div class="sp-km"><span class="sp-dot" style="background:' + tone + '"></span>' + s.km + '</div>' + pts + '</div>'
            + '<div class="sp-ele"><span class="up">↗ ' + s.gain + ' m</span><span class="dn">↘ ' + s.loss + ' m</span></div>'
            + '<div class="sp-grade" style="color:' + gradeColor(s.grade) + '">' + sign + s.grade + '%</div>'
            + '<div class="sp-pace"><div style="min-width:0;flex:1"><div style="display:flex;align-items:center;gap:10px"><b>' + mmss(pace) + '</b><span class="sp-bar"><i style="width:' + w.toFixed(0) + '%;background:' + tone + '"></i></span></div>' + hz + '</div></div>'
            + '<div class="sp-time r"><b>' + (startMin != null ? clock(startMin + elapsed) : hm(elapsed)) + '</b><small>+' + hm(elapsed) + '</small></div>'
            + '</div>';
        });
        const tg = splits.reduce((a, s) => a + s.gain, 0), tl = splits.reduce((a, s) => a + s.loss, 0);
        html += '<div class="sp-sum"><b>' + (course.total_km || 0).toFixed(1) + '</b>'
          + '<span>↗ ' + tg + ' · ↘ ' + tl + '</span><span></span>'
          + '<span>Rata-rata <b>' + mmss(T / (course.total_km || 1)) + '</b> /km</span>'
          + '<b class="r" style="text-align:right">' + hm(T) + '</b></div>';
        splitRows.innerHTML = html;
      }

      const modeInput = plan.querySelector('[data-plan-mode]'), bandLink = plan.querySelector('[data-plan-band]');
      const bandBase = bandLink ? bandLink.getAttribute('href') : null;
      function setMode(m){
        if(modeInput) modeInput.value = m;
        if(bandLink) bandLink.href = bandBase + '?mode=' + m;
      }
      plan.querySelectorAll('[data-plan-view]').forEach(b => b.addEventListener('click', () => {
        const km = b.dataset.planView === 'km';
        setMode(km ? 'km' : 'pos');
        if(typeof render === 'function') render();
        plan.querySelectorAll('[data-plan-view]').forEach(x => x.classList.toggle('on', x === b));
        listEl.hidden = km;
        splitBox.hidden = !km;
      }));
      if(!splits.length) plan.querySelector('.plan-tabs').hidden = true;
      if(modeInput && modeInput.value === 'km' && splits.length){ const kb = plan.querySelector('[data-plan-view="km"]'); kb && kb.click(); }
      else setMode(modeInput ? modeInput.value : 'pos');

      plan.querySelectorAll('[data-plan-step]').forEach(b => b.addEventListener('click', () => {
        range.value = Math.min(Number(range.max), Math.max(Number(range.min), Number(range.value) + Number(b.dataset.planStep)));
        render();
      }));
      range.addEventListener('input', render);
      render();
    }
  })();
</script>
@endverbatim
@endpush
