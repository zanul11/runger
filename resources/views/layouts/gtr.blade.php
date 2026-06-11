<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>@yield('title', 'Gerung Trail Run 2026 — 1st Edition · Runger Anniversary')</title>
<meta name="description" content="@yield('description', 'Gerung Trail Run 2026 — race trail perdana di Lombok Barat. Bukit Keteri, Gerung. Dipersembahkan oleh Runners Gerung.')">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@verbatim
<style>
  :root{
    --bg:#0A0A0A;
    --bg-soft:#141414;
    --card:#1A1A1A;
    --card-2:#222222;
    --line:rgba(255,255,255,.08);
    --line-strong:rgba(255,255,255,.18);
    --text:#FFFFFF;
    --text-soft:#B4B4B4;
    --text-mute:#7A7A7A;
    --red:#E53935;
    --red-deep:#C62828;
    --red-soft:rgba(229,57,53,.12);
  }
  *{box-sizing:border-box;margin:0;padding:0;min-width:0}
  html{scroll-behavior:smooth}
  html,body{
    background:var(--bg);color:var(--text);
    font-family:'Plus Jakarta Sans',sans-serif;-webkit-font-smoothing:antialiased;
    overflow-x:hidden;
  }
  a{color:inherit;text-decoration:none}
  img,video{display:block;max-width:100%}
  button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}
  [id]{scroll-margin-top:78px}
  .wrap{max-width:1320px;margin:0 auto;padding:0 16px;width:100%}

  /* sub-pages (no hero): keep header solid + clear fixed nav */
  body.gtr-sub{padding-top:60px}
  body.gtr-sub nav.top{
    background:rgba(10,10,10,.9);backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(255,255,255,.06);
  }

  /* ===== MOBILE-FIRST BASE ===== */

  /* NAV */
  nav.top{
    position:fixed;top:0;left:0;right:0;z-index:80;
    background:transparent;border-bottom:1px solid transparent;
    transition:background .3s ease, border-color .3s ease, backdrop-filter .3s ease;
  }
  nav.top.scrolled{
    background:rgba(10,10,10,.78);backdrop-filter:blur(14px);
    border-bottom:1px solid rgba(255,255,255,.06);
  }
  .nav-inner{
    max-width:1320px;margin:0 auto;padding:14px 16px;
    display:flex;align-items:center;gap:12px;justify-content:space-between;
  }
  .nav-logo{display:flex;align-items:center;gap:8px}
  .nav-logo img{height:32px;width:auto;border-radius:4px}
  .nav-divider{width:1px;height:22px;background:rgba(255,255,255,.2);display:inline-block}
  .nav-runger{height:24px !important;border-radius:0 !important;opacity:.95}
  .nav-links{display:none}
  .nav-cta{
    display:inline-flex;align-items:center;gap:6px;
    padding:10px 16px;border-radius:999px;
    background:var(--red);color:#fff;
    font-family:'Archivo',sans-serif;font-weight:700;
    font-size:11px;letter-spacing:.08em;text-transform:uppercase;
    transition:background .2s, transform .15s;
  }
  .nav-cta:hover{background:var(--red-deep);transform:translateY(-1px)}
  .nav-cta .arr{
    display:inline-flex;align-items:center;justify-content:center;
    width:18px;height:18px;background:#fff;color:var(--red);
    border-radius:50%;font-size:11px;font-weight:800;
  }
  .nav-burger{
    width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;
    border:1px solid rgba(255,255,255,.18);border-radius:50%;color:#fff;
  }
  .nav-burger span{display:block;width:14px;height:1.5px;background:#fff;position:relative}
  .nav-burger span::before,.nav-burger span::after{
    content:'';position:absolute;left:0;width:14px;height:1.5px;background:#fff;
  }
  .nav-burger span::before{top:-4px}
  .nav-burger span::after{top:4px}

  /* Mobile menu drawer */
  .nav-drawer{
    position:fixed;inset:0;z-index:90;background:rgba(10,10,10,.96);backdrop-filter:blur(16px);
    display:none;flex-direction:column;padding:80px 24px 24px;overflow-y:auto;
  }
  .nav-drawer.open{display:flex}
  .nav-drawer-close{
    position:absolute;top:14px;right:16px;width:38px;height:38px;
    display:inline-flex;align-items:center;justify-content:center;
    border:1px solid rgba(255,255,255,.18);border-radius:50%;color:#fff;font-size:20px;
  }
  .nav-drawer > a{
    padding:18px 0;border-bottom:1px solid var(--line);
    font-family:'Archivo',sans-serif;font-weight:700;
    font-size:18px;letter-spacing:.02em;text-transform:uppercase;color:#fff;
  }
  .nav-drawer > a.active{color:var(--red)}
  .nav-drawer .nav-cta{margin-top:24px;align-self:flex-start}
  /* drawer collapsible submenus */
  .nav-drawer details > summary{
    list-style:none;cursor:pointer;
    padding:18px 0;border-bottom:1px solid var(--line);
    font-family:'Archivo',sans-serif;font-weight:700;font-size:18px;
    letter-spacing:.02em;text-transform:uppercase;color:#fff;
    display:flex;justify-content:space-between;align-items:center;
  }
  .nav-drawer details > summary::-webkit-details-marker{display:none}
  .nav-drawer details > summary::after{content:'+';font-size:22px;color:var(--red);font-weight:700}
  .nav-drawer details[open] > summary::after{content:'–'}
  .nav-drawer .dd-links{display:flex;flex-direction:column;padding:4px 0 8px}
  .nav-drawer .dd-links a{
    padding:12px 0 12px 14px;border-bottom:1px solid var(--line);
    font-family:'Archivo',sans-serif;font-weight:600;font-size:13px;
    letter-spacing:.04em;text-transform:uppercase;color:var(--text-soft);
  }
  .nav-drawer .dd-links a:hover{color:#fff}

  /* SECTION SCAFFOLD */
  section.block{padding:64px 0;background:var(--bg)}
  .block-head{text-align:center;margin-bottom:32px}
  .block-head .eye{
    font-family:'Archivo',sans-serif;font-size:11px;letter-spacing:.22em;
    text-transform:uppercase;color:var(--red);font-weight:700;margin-bottom:12px;
  }
  .block-head h2{
    font-family:'Archivo',sans-serif;font-weight:900;
    font-size:36px;line-height:.95;letter-spacing:-.02em;
    margin-bottom:14px;
  }
  .block-head p{font-size:14.5px;line-height:1.6;color:var(--text-soft);max-width:560px;margin:0 auto}

  /* HERO */
  .hero{
    position:relative;min-height:100vh;color:#fff;overflow:hidden;
    display:flex;align-items:flex-end;
  }
  .hero-video{
    position:absolute;inset:0;width:100%;height:100%;z-index:1;
    object-fit:cover;object-position:center;background:#000;
  }
  .hero-logos{
    position:absolute;top:70px;right:16px;z-index:3;
    display:flex;align-items:center;gap:12px;
  }
  .hero-logos img{height:46px;width:auto;filter:drop-shadow(0 2px 10px rgba(0,0,0,.6))}

  /* OVERVIEW */
  .overview{padding:64px 0 56px;background:var(--bg)}
  .ov-grid{display:flex;flex-direction:column;gap:32px}
  .ov-head .eye{
    font-family:'Archivo',sans-serif;font-size:11px;letter-spacing:.22em;
    text-transform:uppercase;color:var(--red);font-weight:700;margin-bottom:14px;
  }
  .ov-head h2{
    font-family:'Archivo',sans-serif;font-weight:900;
    font-size:42px;line-height:.95;letter-spacing:-.02em;text-transform:uppercase;
    margin-bottom:20px;
  }
  .ov-head p{font-size:14.5px;line-height:1.65;color:var(--text-soft);margin-bottom:14px}
  .ov-photos{
    display:grid;grid-template-columns:1.5fr 1fr;grid-template-rows:1fr 1fr;
    gap:10px;height:360px;
  }
  .ov-photo{
    border-radius:18px;overflow:hidden;background:#222;
    background-size:cover;background-position:center;
  }
  .ov-photo.big{grid-row:1 / span 2}

  /* INFO STRIP */
  .info-strip{
    margin-top:36px;padding:24px 18px;border-radius:18px;
    background:var(--card);border:1px solid var(--line);
    display:flex;flex-direction:column;gap:24px;
  }
  .info-block{display:flex;align-items:center;gap:14px}
  .info-block .ic{
    width:44px;height:44px;flex-shrink:0;border-radius:10px;
    background:var(--red-soft);color:var(--red);
    display:flex;align-items:center;justify-content:center;
  }
  .info-block .ic svg{width:22px;height:22px}
  .info-block .lab{
    font-family:'Archivo',sans-serif;font-size:10.5px;letter-spacing:.16em;
    text-transform:uppercase;color:var(--text-mute);font-weight:600;margin-bottom:3px;
  }
  .info-block .val{font-family:'Archivo',sans-serif;font-weight:700;font-size:16px;color:#fff;line-height:1.2}
  .info-block .sub{font-size:12px;color:var(--text-mute);margin-top:2px}
  .countdown-block{display:flex;flex-direction:column;gap:8px;flex:1}
  .cd-row{display:flex;align-items:center;gap:14px}
  .cd-row .ic{
    width:44px;height:44px;flex-shrink:0;border-radius:10px;
    background:var(--red-soft);color:var(--red);
    display:flex;align-items:center;justify-content:center;
  }
  .cd-row .lab{
    font-family:'Archivo',sans-serif;font-size:10.5px;letter-spacing:.16em;
    text-transform:uppercase;color:var(--text-mute);font-weight:600;
  }
  .cd-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:4px}
  .cd-cell{text-align:center}
  .cd-num{font-family:'Archivo',sans-serif;font-weight:800;font-size:30px;line-height:1;color:var(--red)}
  .cd-lab{font-family:'Archivo',sans-serif;font-size:9.5px;letter-spacing:.16em;text-transform:uppercase;color:var(--text-mute);margin-top:4px;font-weight:600}

  /* BTN */
  .btn-primary{
    display:inline-flex;align-items:center;gap:10px;
    padding:15px 22px;border-radius:999px;
    background:var(--red);color:#fff;
    font-family:'Archivo',sans-serif;font-weight:700;
    font-size:12px;letter-spacing:.1em;text-transform:uppercase;
    transition:background .2s, transform .15s;
  }
  .btn-primary:hover{background:var(--red-deep);transform:translateY(-2px)}
  .btn-primary .arr{
    display:inline-flex;align-items:center;justify-content:center;
    width:22px;height:22px;background:#fff;color:var(--red);
    border-radius:50%;font-size:12px;font-weight:800;
  }

  /* ===== RACE CATEGORY CARDS (photo concept) ===== */
  .race-grid{display:flex;flex-direction:column;gap:24px}
  .cat-card{
    position:relative;border-radius:22px;overflow:hidden;isolation:isolate;
    min-height:460px;display:flex;flex-direction:column;justify-content:flex-end;
    background-size:cover;background-position:center;background-color:#161616;
    border:1px solid rgba(255,255,255,.08);
    transition:transform .35s cubic-bezier(.22,1,.36,1), box-shadow .35s ease;
    will-change:transform;
  }
  .cat-card::before{
    content:'';position:absolute;inset:0;z-index:1;
    background:linear-gradient(180deg, rgba(0,0,0,.35) 0%, rgba(0,0,0,.12) 34%, rgba(0,0,0,.62) 64%, rgba(0,0,0,.94) 100%);
    transition:opacity .35s ease;
  }
  .cat-card:hover{transform:translateY(-8px);box-shadow:0 38px 80px rgba(0,0,0,.6)}
  .cat-card:hover::before{opacity:.88}
  .cat-top{
    position:absolute;top:16px;left:16px;right:16px;z-index:3;
    display:flex;justify-content:space-between;align-items:flex-start;gap:8px;
  }
  .cat-tag-left{
    display:inline-flex;align-items:center;gap:8px;padding:7px 12px;border-radius:999px;
    background:rgba(0,0,0,.5);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,.16);
    font-family:'Archivo',sans-serif;font-weight:800;font-size:11px;letter-spacing:.04em;color:#fff;
  }
  .cat-tag-left .dot{width:8px;height:8px;border-radius:50%}
  .cat-name-pill{
    display:inline-block;padding:8px 14px;background:var(--red);color:#fff;border-radius:999px;
    font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.03em;
    box-shadow:0 8px 22px rgba(229,57,53,.45);
  }
  .cat-inner{position:relative;z-index:2;padding:24px 22px 22px;display:flex;flex-direction:column}
  .cat-dist{font-family:'Archivo',sans-serif;font-weight:900;font-size:54px;line-height:.9;letter-spacing:-.01em;color:#fff;margin-bottom:12px}
  .cat-desc{font-size:13.5px;line-height:1.55;color:rgba(255,255,255,.82);margin-bottom:20px;max-width:42ch}
  .cat-rows{display:flex;flex-direction:column;margin-bottom:20px;border-top:1px solid rgba(255,255,255,.16)}
  .cat-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid rgba(255,255,255,.16)}
  .cat-row .k{font-size:13px;color:rgba(255,255,255,.72)}
  .cat-row .v{font-family:'Archivo',sans-serif;font-weight:800;font-size:14.5px;color:#fff;text-align:right}
  .cat-row .v.strike{
    position:relative;color:rgba(255,255,255,.5);font-weight:700;
    text-decoration:line-through;text-decoration-color:var(--red);text-decoration-thickness:2px;
  }
  .cat-btn{
    display:inline-flex;align-items:center;justify-content:center;gap:10px;
    padding:16px;border-radius:12px;
    background:linear-gradient(180deg,#FF5A56 0%,var(--red) 55%,var(--red-deep) 100%);color:#fff;
    font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;letter-spacing:.1em;text-transform:uppercase;
    box-shadow:0 14px 32px rgba(229,57,53,.35);
    transition:transform .15s, filter .2s, letter-spacing .3s ease;
  }
  .cat-btn:hover{transform:translateY(-1px);filter:brightness(1.06);letter-spacing:.14em}
  .cat-btn .arr{transition:transform .2s}
  .cat-card:hover .cat-btn .arr{transform:translateX(4px)}

  /* RULES */
  .rules-list{display:flex;flex-direction:column;gap:12px}
  .rule{
    background:var(--card);border:1px solid var(--line);border-radius:14px;
    padding:18px 18px;display:flex;gap:14px;align-items:flex-start;
  }
  .rule .num{
    width:34px;height:34px;flex-shrink:0;border-radius:50%;
    background:var(--red-soft);color:var(--red);
    display:flex;align-items:center;justify-content:center;
    font-family:'Archivo',sans-serif;font-weight:800;font-size:13px;
  }
  .rule h4{
    font-family:'Archivo',sans-serif;font-weight:800;font-size:14.5px;
    letter-spacing:.01em;text-transform:uppercase;margin-bottom:5px;color:#fff;
  }
  .rule p{font-size:13.5px;line-height:1.55;color:var(--text-soft)}

  /* TABLE PLACEHOLDER */
  .placeholder{
    background:var(--card);border:1px solid var(--line);border-radius:18px;
    padding:48px 22px;text-align:center;
  }
  .placeholder .ph-icon{
    width:54px;height:54px;margin:0 auto 16px;border-radius:14px;
    background:var(--red-soft);color:var(--red);
    display:flex;align-items:center;justify-content:center;
  }
  .placeholder .ph-icon svg{width:26px;height:26px}
  .placeholder .ph-title{
    font-family:'Archivo',sans-serif;font-weight:800;font-size:18px;
    letter-spacing:.01em;text-transform:uppercase;margin-bottom:8px;
  }
  .placeholder .ph-sub{font-size:13.5px;color:var(--text-soft);line-height:1.5;max-width:420px;margin:0 auto 18px}
  .placeholder .ph-badge{
    display:inline-flex;align-items:center;gap:8px;padding:7px 14px;
    background:rgba(229,57,53,.12);color:var(--red);
    border:1px solid rgba(229,57,53,.35);border-radius:999px;
    font-family:'Archivo',sans-serif;font-size:11px;font-weight:700;
    letter-spacing:.16em;text-transform:uppercase;
  }
  .placeholder .ph-badge .pulse{width:6px;height:6px;background:var(--red);border-radius:50%;animation:pulse 1.6s infinite}
  @keyframes pulse{0%,100%{opacity:1}50%{opacity:.35}}

  /* SPONSOR / CONTACT */
  .sponsor-section{
    position:relative;padding:80px 0 88px;overflow:hidden;
    background:radial-gradient(ellipse 600px 300px at 20% 0%, rgba(229,57,53,.18), transparent 70%);
  }
  .sponsor-inner{text-align:center}
  .sponsor-inner h2{
    font-family:'Archivo',sans-serif;font-weight:900;
    font-size:34px;line-height:.95;letter-spacing:-.02em;color:#fff;margin-bottom:28px;
  }
  .sponsor-row{
    display:flex;align-items:center;justify-content:center;gap:24px;flex-wrap:wrap;
    margin:24px 0 36px;opacity:.65;
  }
  .sponsor-slot{
    padding:14px 26px;border:1px dashed rgba(255,255,255,.2);border-radius:14px;
    font-family:'Archivo',sans-serif;font-size:11px;letter-spacing:.18em;
    text-transform:uppercase;color:var(--text-mute);font-weight:600;
  }
  .sponsor-cta{
    margin-top:24px;padding:26px 22px;background:var(--card);border:1px solid var(--line);
    border-radius:18px;
  }
  .sponsor-cta h3{font-family:'Archivo',sans-serif;font-weight:800;font-size:18px;margin-bottom:8px}
  .sponsor-cta p{font-size:13.5px;color:var(--text-soft);line-height:1.55;margin-bottom:18px}
  .sponsor-cta .btn-primary{justify-content:center}

  /* CONTACT INFO */
  .contact-grid{display:grid;grid-template-columns:1fr;gap:12px;margin-top:24px}
  .contact-item{
    background:var(--card);border:1px solid var(--line);border-radius:14px;
    padding:16px 18px;display:flex;align-items:center;gap:14px;
  }
  .contact-item .ic{
    width:40px;height:40px;flex-shrink:0;border-radius:10px;
    background:var(--red-soft);color:var(--red);
    display:flex;align-items:center;justify-content:center;
  }
  .contact-item .ic svg{width:18px;height:18px}
  .contact-item .lab{
    font-family:'Archivo',sans-serif;font-size:10.5px;letter-spacing:.16em;
    text-transform:uppercase;color:var(--text-mute);font-weight:600;margin-bottom:3px;
  }
  .contact-item .val{font-family:'Archivo',sans-serif;font-weight:700;font-size:14px;color:#fff}

  /* ===== CATEGORY DETAIL ===== */
  .cat-hero{
    position:relative;min-height:52vh;display:flex;align-items:flex-end;overflow:hidden;color:#fff;
    background-size:cover;background-position:center;background-color:#111;
  }
  .detail-section{padding:32px 0 56px;background:var(--bg)}
  .cat-hero::before{
    content:'';position:absolute;inset:0;
    background:linear-gradient(180deg, rgba(0,0,0,.4) 0%, rgba(0,0,0,.2) 38%, rgba(0,0,0,.92) 100%);
  }
  .cat-hero-inner{position:relative;z-index:2;width:100%;max-width:1320px;margin:0 auto;padding:0 16px 44px}
  .cat-hero .back{
    display:inline-flex;align-items:center;gap:8px;margin-bottom:18px;
    font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;letter-spacing:.08em;
    text-transform:uppercase;color:rgba(255,255,255,.85);
  }
  .cat-hero .back:hover{color:#fff}
  .cat-hero .ch-tag{
    display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border-radius:999px;margin-bottom:14px;
    background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(6px);
    font-family:'Archivo',sans-serif;font-weight:800;font-size:11px;letter-spacing:.04em;text-transform:uppercase;
  }
  .cat-hero .ch-tag .dot{width:8px;height:8px;border-radius:50%}
  .cat-hero h1{font-family:'Archivo',sans-serif;font-weight:900;font-size:clamp(48px,11vw,110px);line-height:.9;letter-spacing:-.02em;text-transform:uppercase}
  .cat-hero .ch-name{font-family:'Archivo',sans-serif;font-weight:700;font-size:clamp(15px,3vw,22px);color:#fff;text-transform:uppercase;letter-spacing:.04em;margin-top:8px;opacity:.92}

  .detail-wrap{max-width:1320px;margin:0 auto;padding:0 16px}
  .cat-stats{
    display:grid;grid-template-columns:repeat(2,1fr);gap:1px;background:var(--line);
    border:1px solid var(--line);border-radius:16px;overflow:hidden;
  }
  .cat-stat{background:#101010;padding:22px 18px}
  .cat-stat .lab{font-family:'Archivo',sans-serif;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--text-mute);font-weight:700;margin-bottom:9px}
  .cat-stat .val{font-family:'Archivo',sans-serif;font-weight:800;font-size:19px;color:#fff;line-height:1.15}
  .cat-stat .val .strike{font-size:14px;color:rgba(255,255,255,.45);text-decoration:line-through;text-decoration-color:var(--red);margin-left:6px;font-weight:700}
  .cat-stat .sub{font-size:11.5px;color:var(--text-mute);margin-top:5px}

  /* tabs — white panel */
  .tabs{
    margin-top:26px;background:#fff;color:#101010;border-radius:18px;
    padding:6px 22px 24px;box-shadow:0 18px 50px rgba(0,0,0,.35);
  }
  .tab-btns{display:flex;flex-wrap:wrap;gap:4px;border-bottom:1px solid #E6E6E6;margin-bottom:22px}
  .tab-btn{
    padding:14px 16px;font-family:'Archivo',sans-serif;font-weight:700;font-size:12.5px;
    letter-spacing:.04em;text-transform:uppercase;color:#9A9A9A;
    border:none;border-bottom:2px solid transparent;margin-bottom:-1px;background:none;cursor:pointer;
    transition:color .2s, border-color .2s;
  }
  .tab-btn:hover{color:#101010}
  .tab-btn.active{color:#101010;border-color:var(--red)}
  .tab-panel{display:none}
  .tab-panel.active{display:block;animation:tabIn .3s ease}
  @keyframes tabIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
  .tab-img{width:100%;max-height:280px;object-fit:cover;border-radius:14px;border:1px solid #E6E6E6;display:block}
  .tab-note{font-size:13px;color:#8A8A8A;margin-top:12px}
  .rundown{display:flex;flex-direction:column}
  .rundown-row{display:flex;gap:18px;padding:14px 0;border-bottom:1px solid #EDEDED;align-items:flex-start}
  .rundown-row:last-child{border-bottom:none}
  .rundown-row .t{display:flex;flex-direction:column;gap:2px;min-width:130px;flex-shrink:0}
  .rundown-row .t .d{font-family:'Archivo',sans-serif;font-size:10.5px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#9A9A9A}
  .rundown-row .t .hh{font-family:'Archivo',sans-serif;font-weight:800;font-size:15px;color:var(--red)}
  .rundown-row .a{display:flex;flex-direction:column;gap:3px}
  .rundown-row .a .act{font-size:14.5px;color:#101010;font-weight:500}
  .rundown-row .a .loc{font-size:12.5px;color:#8A8A8A;font-weight:600}
  .gear-list{display:grid;grid-template-columns:1fr;gap:10px}
  .gear-item{display:flex;gap:12px;align-items:center;padding:12px 16px;background:#F6F6F6;border:1px solid #ECECEC;border-radius:12px}
  .gear-item svg{width:18px;height:18px;color:var(--red);flex-shrink:0}
  .gear-item span{font-size:14px;color:#101010}
  .gpx-box{
    display:flex;flex-direction:column;align-items:flex-start;gap:14px;
    background:#F6F6F6;border:1px solid #ECECEC;border-radius:16px;padding:24px 22px;
  }
  .gpx-box p{font-size:14px;color:#555;line-height:1.55;max-width:520px}
  .gpx-box p strong{color:#101010}

  /* SUPPORTED BY */
  .support-section{padding:56px 0;background:var(--bg)}
  .support-grid{display:flex;flex-wrap:wrap;justify-content:center;gap:28px;margin-top:8px}
  .support-item{display:flex;flex-direction:column;align-items:center;text-align:center;gap:16px;max-width:300px}
  .support-logo{
    height:120px;display:flex;align-items:center;justify-content:center;transition:transform .3s ease;
  }
  .support-item:hover .support-logo{transform:translateY(-5px)}
  .support-logo img{max-height:100%;width:auto;object-fit:contain;filter:drop-shadow(0 6px 18px rgba(0,0,0,.45))}
  .support-name{
    font-family:'Archivo',sans-serif;font-weight:700;font-size:13px;line-height:1.45;
    color:var(--text-soft);text-transform:uppercase;letter-spacing:.04em;
  }

  /* SCENIC COURSE GALLERY */
  .course-section{padding:56px 0;background:var(--bg)}
  .course-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-top:8px}
  .course-tile{
    position:relative;display:block;aspect-ratio:1/1;overflow:hidden;background:#161616;
  }
  .course-tile img{width:100%;height:100%;object-fit:cover;transition:transform .5s cubic-bezier(.22,1,.36,1)}
  .course-tile::after{
    content:'';position:absolute;inset:0;z-index:1;
    background:linear-gradient(180deg, rgba(0,0,0,.1) 0%, rgba(0,0,0,.28) 55%, rgba(0,0,0,.55) 100%);
    transition:background .3s ease;
  }
  .course-tile:hover img{transform:scale(1.07)}
  .course-tile:hover::after{background:linear-gradient(180deg, rgba(0,0,0,.05), rgba(0,0,0,.32))}
  .course-tile .label{
    position:absolute;inset:0;z-index:2;display:flex;align-items:center;justify-content:center;
    text-align:center;padding:10px;
    font-family:'Archivo',sans-serif;font-weight:800;font-size:clamp(15px,4.4vw,20px);
    letter-spacing:.03em;text-transform:uppercase;color:#fff;text-shadow:0 2px 14px rgba(0,0,0,.7);
  }

  /* FOOTER */
  footer{background:#050505;border-top:1px solid var(--line);padding:36px 16px 24px}
  .foot-grid{
    max-width:1320px;margin:0 auto;display:flex;flex-direction:column;gap:24px;
    padding-bottom:24px;border-bottom:1px solid var(--line);
  }
  .foot-brand{display:flex;align-items:center;gap:14px;flex-wrap:wrap}
  .foot-brand > img{height:42px;border-radius:6px}
  .foot-brand-text{font-family:'Archivo',sans-serif;font-weight:900;font-size:18px;line-height:1;text-transform:uppercase}
  .foot-brand-text .accent{color:var(--red)}
  .foot-brand-sub{font-size:11px;color:var(--text-mute);letter-spacing:.16em;text-transform:uppercase;margin-top:4px;font-family:'Archivo',sans-serif;font-weight:600}
  .foot-by{display:flex;align-items:center;gap:8px;padding:8px 12px;border-left:1px solid var(--line)}
  .foot-by .lab{font-family:'Archivo',sans-serif;font-size:9px;letter-spacing:.2em;text-transform:uppercase;color:var(--text-mute);font-weight:600}
  .foot-by img{height:26px;opacity:.95}
  .foot-nav{display:flex;flex-wrap:wrap;gap:18px}
  .foot-nav a{
    font-family:'Archivo',sans-serif;font-size:12px;letter-spacing:.06em;
    text-transform:uppercase;color:var(--text-soft);font-weight:600;transition:color .2s;
  }
  .foot-nav a:hover{color:var(--red)}
  .foot-bottom{
    max-width:1320px;margin:0 auto;padding-top:18px;
    display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;
    font-family:'Archivo',sans-serif;font-size:11px;letter-spacing:.14em;
    text-transform:uppercase;color:var(--text-mute);font-weight:500;
  }

  /* ===== DESKTOP (≥820px) ===== */
  @media (min-width:820px){
    .wrap{padding:0 32px}
    .nav-inner{padding:18px 32px}
    .nav-logo img{height:42px}
    .nav-burger{display:none}
    .nav-links{
      display:flex;align-items:center;gap:28px;
    }
    .nav-item{position:relative;display:inline-flex;align-items:center}
    .nav-link{
      color:var(--text-soft);transition:color .15s;position:relative;padding:8px 0;
      font-family:'Archivo',sans-serif;font-size:13px;letter-spacing:.04em;
      text-transform:uppercase;font-weight:700;background:none;cursor:pointer;
      display:inline-flex;align-items:center;gap:6px;
    }
    .nav-link:hover{color:#fff}
    .nav-link.active{color:#fff}
    .nav-link.active::after{content:'';position:absolute;left:0;right:0;bottom:-2px;height:2px;background:var(--red)}
    .nav-link .caret{font-size:8px;transition:transform .2s}
    .nav-item:hover .nav-link .caret{transform:rotate(180deg)}
    .nav-dd{
      position:absolute;top:100%;left:0;min-width:248px;
      background:#141414;border:1px solid var(--line);border-radius:14px;
      padding:8px;display:flex;flex-direction:column;gap:2px;
      opacity:0;visibility:hidden;transform:translateY(8px);
      transition:opacity .18s ease, transform .18s ease, visibility .18s;
      box-shadow:0 22px 60px rgba(0,0,0,.55);z-index:90;
    }
    .nav-dd::before{content:'';position:absolute;top:-12px;left:0;right:0;height:12px}
    .nav-item:hover .nav-dd{opacity:1;visibility:visible;transform:translateY(0)}
    .nav-dd a{
      padding:10px 12px;border-radius:9px;
      font-family:'Archivo',sans-serif;font-size:11.5px;letter-spacing:.05em;
      text-transform:uppercase;font-weight:600;color:var(--text-soft);white-space:nowrap;
    }
    .nav-dd a:hover{background:rgba(255,255,255,.07);color:#fff}
    .nav-cta{padding:12px 22px;font-size:12px}
    .nav-cta .arr{width:22px;height:22px;font-size:12px}

    .hero-inner{padding:0 32px 80px}
    .hero-logos{top:96px;right:32px;gap:18px}
    .hero-logos img{height:66px}

    /* Overview */
    .overview{padding:120px 0 100px}
    .ov-grid{display:grid;grid-template-columns:1fr 1.1fr;gap:60px;align-items:center}
    .ov-head h2{font-size:clamp(48px,6vw,72px);margin-bottom:28px}
    .ov-head p{font-size:16px;margin-bottom:18px}
    .ov-photos{height:540px;gap:14px}
    .info-strip{flex-direction:row;align-items:stretch;justify-content:space-between;padding:26px 32px;gap:32px;margin-top:48px}
    .info-block,.countdown-block{flex:1}
    .countdown-block{flex:1.4}
    .cd-grid{margin-top:6px}
    .cd-num{font-size:38px}

    /* Sections */
    section.block{padding:120px 0}
    .block-head{margin-bottom:48px}
    .block-head h2{font-size:clamp(40px,5vw,60px);margin-bottom:18px}
    .block-head p{font-size:16px}

    /* Category cards — 2 full-width cols */
    .race-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:28px}
    .cat-card{min-height:520px}
    .cat-dist{font-size:62px}

    /* Rules — 2 cols */
    .rules-list{display:grid;grid-template-columns:1fr 1fr;gap:14px}

    /* Contact */
    .contact-grid{grid-template-columns:repeat(3,1fr);gap:16px}

    /* Category detail */
    .cat-hero-inner{padding:0 32px 60px}
    .detail-wrap{padding:0 32px}
    .cat-stats{grid-template-columns:repeat(3,1fr)}
    .cat-stat{padding:26px 24px}
    .cat-stat .val{font-size:22px}
    .gear-list{grid-template-columns:1fr 1fr;gap:12px}

    /* Supported by */
    .support-section{padding:96px 0}
    .support-grid{gap:56px}
    .support-logo{height:150px}

    /* Scenic course */
    .course-section{padding:110px 0}
    .course-grid{grid-template-columns:repeat(4,1fr);gap:14px}
    .course-tile .label{font-size:24px}

    /* Sponsor */
    .sponsor-section{padding:140px 0}
    .sponsor-inner h2{font-size:clamp(40px,5vw,58px)}
    .sponsor-cta{padding:32px 28px;max-width:540px;margin:24px auto 0}

    /* Footer */
    footer{padding:48px 32px 24px}
    .foot-grid{flex-direction:row;align-items:center;justify-content:space-between;gap:32px;padding-bottom:32px}

    body.gtr-sub{padding-top:78px}
  }
</style>
@endverbatim
@stack('styles')
</head>
<body class="@yield('bodyClass')">
@php
  $navActive = $active ?? 'home';
  $gtrNavCats = \App\Models\GtrCategory::where('is_active', true)->orderBy('sort_order')->get(['slug', 'name']);
@endphp

<!-- NAV -->
<nav class="top">
  <div class="nav-inner">
    <a class="nav-logo" href="{{ route('gtr') }}">
      <img src="{{ asset('assets/gtr/logo.jpeg') }}" alt="Gerung Trail Run">
      <span class="nav-divider"></span>
      <img class="nav-runger" src="{{ asset('assets/gtr/logo-white.png') }}" alt="Runger · Runners Gerung">
    </a>
    <div class="nav-links">
      <a href="{{ route('gtr') }}" @class(['nav-link', 'active' => $navActive === 'home'])>Home</a>

      <div class="nav-item">
        <span class="nav-link">Race Info <span class="caret">▾</span></span>
        <div class="nav-dd">
          <a href="{{ route('gtr') }}#overview">Race Venue</a>
          <a href="{{ route('gtr') }}#overview">Event Schedule</a>
          <a href="{{ route('gtr.rules') }}">Rules and Regulations</a>
          <a href="{{ route('gtr.rules') }}">Mandatory Gear</a>
          <a href="{{ route('gtr.rules') }}">Race Pack Collection</a>
          <a href="#">Merchandise</a>
          <a href="#">Accomodation</a>
          <a href="#">Download Race Guide</a>
          <a href="#">Download GPX</a>
          <a href="#">Download Waiver Letter</a>
          <a href="#">Download PARQ</a>
          <a href="#">Download Surat Kuasa</a>
          <a href="{{ asset('assets/gtr/logo.jpeg') }}" download>Download Logo</a>
        </div>
      </div>

      <div class="nav-item">
        <span class="nav-link">Categories <span class="caret">▾</span></span>
        <div class="nav-dd">
          @foreach($gtrNavCats as $c)
            <a href="{{ route('gtr.category', $c->slug) }}">{{ strtoupper($c->slug) }} — {{ $c->name }}</a>
          @endforeach
        </div>
      </div>

      <a href="{{ route('gtr.entry') }}" @class(['nav-link', 'active' => $navActive === 'entry'])>Entry List</a>
      <a href="{{ route('gtr.results') }}" @class(['nav-link', 'active' => $navActive === 'results'])>Results</a>
      <a href="{{ route('gtr') }}#contact" class="nav-link">Contact</a>
    </div>
    @auth('runner')
      <a class="nav-cta" href="{{ route('gtr.dashboard') }}">{{ auth('runner')->user()->first_name }} · Akun Saya</a>
    @else
      <a class="nav-cta" href="{{ route('gtr.login') }}">Register Now <span class="arr">→</span></a>
    @endauth
    <button class="nav-burger" id="nav-burger" aria-label="Open menu"><span></span></button>
  </div>
</nav>

<!-- MOBILE DRAWER -->
<div class="nav-drawer" id="nav-drawer" aria-hidden="true">
  <button class="nav-drawer-close" id="nav-close" aria-label="Close menu">×</button>
  <a href="{{ route('gtr') }}" @class(['active' => $navActive === 'home'])>Home</a>
  <details>
    <summary>Race Info</summary>
    <div class="dd-links">
      <a href="{{ route('gtr') }}#overview">Race Venue</a>
      <a href="{{ route('gtr') }}#overview">Event Schedule</a>
      <a href="{{ route('gtr.rules') }}">Rules and Regulations</a>
      <a href="{{ route('gtr.rules') }}">Mandatory Gear</a>
      <a href="{{ route('gtr.rules') }}">Race Pack Collection</a>
      <a href="#">Merchandise</a>
      <a href="#">Accomodation</a>
      <a href="#">Download Race Guide</a>
      <a href="#">Download GPX</a>
      <a href="#">Download Waiver Letter</a>
      <a href="#">Download PARQ</a>
      <a href="#">Download Surat Kuasa</a>
      <a href="{{ asset('assets/gtr/logo.jpeg') }}" download>Download Logo</a>
    </div>
  </details>
  <details>
    <summary>Categories</summary>
    <div class="dd-links">
      @foreach($gtrNavCats as $c)
        <a href="{{ route('gtr.category', $c->slug) }}">{{ strtoupper($c->slug) }} — {{ $c->name }}</a>
      @endforeach
    </div>
  </details>
  <a href="{{ route('gtr.entry') }}" @class(['active' => $navActive === 'entry'])>Entry List</a>
  <a href="{{ route('gtr.results') }}" @class(['active' => $navActive === 'results'])>Results</a>
  <a href="{{ route('gtr') }}#contact">Contact</a>
  <a class="nav-cta" href="{{ route('volunteer') }}">Register Now <span class="arr">→</span></a>
</div>

@yield('content')

<!-- FOOTER -->
<footer>
  <div class="foot-grid">
    <div class="foot-brand">
      <img src="{{ asset('assets/gtr/logo.jpeg') }}" alt="Gerung Trail Run">
      <div>
        <div class="foot-brand-text">Gerung Trail Run <span class="accent">2026.</span></div>
        <div class="foot-brand-sub">1st Edition · Bukit Keteri Trail</div>
      </div>
      <div class="foot-by">
        <span class="lab">Presented by</span>
        <img src="{{ asset('assets/gtr/logo-white.png') }}" alt="Runger · Runners Gerung">
      </div>
    </div>
    <div class="foot-nav">
      <a href="{{ route('home') }}">Runger Home</a>
      <a href="{{ route('gtr') }}#category">Category</a>
      <a href="{{ route('gtr.entry') }}">Entry List</a>
      <a href="{{ route('gtr.results') }}">Results</a>
      <a href="{{ route('gtr.rules') }}">Rules</a>
      <a href="{{ route('gtr') }}#contact">Contact</a>
    </div>
  </div>
  <div class="foot-bottom">
    <div>© {{ date('Y') }} Runger · Gerung Trail Run</div>
    <div>Gerung, Lombok Barat · NTB · Indonesia</div>
  </div>
</footer>

@verbatim
<script>
  // Mobile drawer toggle
  const burger = document.getElementById('nav-burger');
  const drawer = document.getElementById('nav-drawer');
  const closeBtn = document.getElementById('nav-close');
  burger?.addEventListener('click', () => drawer.classList.add('open'));
  closeBtn?.addEventListener('click', () => drawer.classList.remove('open'));
  drawer?.querySelectorAll('a').forEach(a => a.addEventListener('click', () => drawer.classList.remove('open')));

  // Transparent header until scrolled
  const topNav = document.querySelector('nav.top');
  function onNavScroll(){ topNav?.classList.toggle('scrolled', window.scrollY > 40); }
  window.addEventListener('scroll', onNavScroll, {passive:true});
  onNavScroll();

  // Countdown to 29 Nov 2026, 05:30 WITA (only if present)
  const cdD = document.getElementById('cd-d');
  if(cdD){
    const EVENT_UTC = Date.UTC(2026, 10, 29, 5, 30) - 8*3600000;
    const target = new Date(EVENT_UTC);
    const pad = n => String(Math.max(0,n)).padStart(2,'0');
    const set = (id,v) => { const el = document.getElementById(id); if(el) el.textContent = v; };
    function tick(){
      const diff = target - new Date();
      if(diff <= 0){ ['cd-d','cd-h','cd-m','cd-s'].forEach(id => set(id,'00')); return; }
      set('cd-d', pad(Math.floor(diff/86400000)));
      set('cd-h', pad(Math.floor(diff/3600000) % 24));
      set('cd-m', pad(Math.floor(diff/60000) % 60));
      set('cd-s', pad(Math.floor(diff/1000) % 60));
    }
    tick(); setInterval(tick, 1000);
  }
</script>
@endverbatim
@stack('scripts')
</body>
</html>
