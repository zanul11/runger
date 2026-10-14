<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="theme-color" content="#0B1A5C">
<title>@yield('title', 'Gerung Trail Run — Akun Peserta')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="preload" as="image" href="{{ asset('assets/runger-logo-white.webp') }}" type="image/webp">
@verbatim
<style>
  :root{
    --red:#E53935;--red-deep:#C62828;
    --blue:#1B3FAE;--blue-bright:#2F5BE0;--navy:#0B1A5C;--navy-deep:#060E38;
    --ink:#0F1115;--line:#E4E7EF;--muted:#8A90A2
  }
  *{box-sizing:border-box;margin:0;padding:0}
  html{background:var(--navy-deep)}
  body{font-family:'Poppins',sans-serif;color:var(--ink);-webkit-font-smoothing:antialiased;min-height:100vh;background:var(--navy-deep)}
  a{color:inherit;text-decoration:none}

  .auth{min-height:100vh;display:flex;flex-direction:column}

  /* ===== Hero: logo Runger sebagai bintang utama ===== */
  .hero{
    position:relative;overflow:hidden;color:#fff;isolation:isolate;
    padding:calc(46px + env(safe-area-inset-top)) 24px 118px;
    display:flex;flex-direction:column;align-items:center;text-align:center;
  }
  .hero-photo{position:absolute;inset:0;z-index:-3;background-size:cover;background-position:center 30%;transform:scale(1.06);animation:drift 22s ease-in-out infinite alternate}
  .hero::before{
    content:'';position:absolute;inset:0;z-index:-2;
    background:
      radial-gradient(120% 80% at 50% 0%,rgba(47,91,224,.55) 0%,rgba(27,63,174,0) 60%),
      linear-gradient(180deg,rgba(27,63,174,.80) 0%,rgba(11,26,92,.86) 55%,rgba(6,14,56,.97) 100%);
  }
  /* garis kontur topografi */
  .hero::after{
    content:'';position:absolute;inset:-20%;z-index:-1;opacity:.13;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='400' viewBox='0 0 400 400'%3E%3Cg fill='none' stroke='%23fff' stroke-width='1.2'%3E%3Cpath d='M-20 80c60-40 120 30 190-5s140-50 250 10'/%3E%3Cpath d='M-20 120c70-35 130 35 200 0s150-45 240 15'/%3E%3Cpath d='M-20 165c55-30 125 40 205 5s145-40 235 18'/%3E%3Cpath d='M-20 215c65-38 135 32 210-2s140-48 230 12'/%3E%3Cpath d='M-20 262c60-28 128 38 198 4s152-42 242 16'/%3E%3Cpath d='M-20 310c70-36 132 30 206-3s144-46 234 14'/%3E%3Cpath d='M-20 356c58-30 126 36 200 3s150-44 240 15'/%3E%3C/g%3E%3C/svg%3E");
    background-size:400px 400px;transform:rotate(-8deg)
  }
  .hero-home{display:inline-block;color:inherit;text-decoration:none}
  .hero-logo{
    width:min(82vw,330px);height:auto;display:block;
    filter:drop-shadow(0 10px 30px rgba(0,0,0,.35));
    animation:rise .8s cubic-bezier(.2,.8,.2,1) both
  }
  .hero-tag{
    margin-top:18px;display:inline-flex;align-items:center;gap:10px;
    font-weight:700;font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.88);
    animation:rise .8s .12s cubic-bezier(.2,.8,.2,1) both
  }
  .hero-tag::before,.hero-tag::after{content:'';width:22px;height:2px;background:var(--red);border-radius:2px}
  .hero-copy,.hero-feats{display:none}

  /* profil elevasi trail di dasar hero */
  .hero-trail{position:absolute;left:0;right:0;bottom:40px;width:100%;height:56px;z-index:-1;opacity:.6}
  .hero-trail path.line{fill:none;stroke:#fff;stroke-width:2;stroke-dasharray:6 7;stroke-linecap:round;animation:march 30s linear infinite}
  .hero-trail path.fill{fill:url(#trailFill)}
  .hero-trail circle{fill:var(--red);stroke:#fff;stroke-width:2.5}

  /* ===== Panel form (bottom-sheet di HP) ===== */
  .auth-panel{
    flex:1;position:relative;z-index:2;margin-top:-40px;
    background:#fff;border-radius:30px 30px 0 0;
    box-shadow:0 -18px 50px rgba(0,0,0,.25);
    display:flex;justify-content:center;
    animation:sheet .7s .1s cubic-bezier(.2,.8,.2,1) both
  }
  .auth-panel::before{content:'';position:absolute;top:10px;left:50%;width:44px;height:5px;margin-left:-22px;border-radius:5px;background:#E3E6EE}
  .auth-form{width:100%;max-width:444px;padding:34px 22px calc(26px + env(safe-area-inset-bottom))}

  .auth-eyebrow{
    text-align:center;font-weight:800;font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--red);margin-bottom:7px;
  }
  .auth-title{font-weight:900;font-size:28px;text-align:center;letter-spacing:-.02em;line-height:1.05}
  .auth-sub{text-align:center;color:var(--muted);margin:9px 0 24px;font-size:14px}

  .fld{margin-bottom:15px}
  .fld > label{display:block;font-weight:700;font-size:12.5px;margin-bottom:7px;color:#2b2f3a}
  .fld .req{color:var(--red)}
  .inp{width:100%;padding:13px 15px;border:1.5px solid var(--line);border-radius:12px;font-size:14px;font-family:inherit;background:#fbfcfe;color:var(--ink);transition:border-color .15s, box-shadow .15s, background .15s}
  .inp::placeholder{color:#b3b8c6}
  .inp:focus{outline:none;border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(27,63,174,.12)}
  select.inp{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
  .row2{display:contents}
  .phone-row{display:grid;grid-template-columns:118px 1fr;gap:10px}
  .err{color:var(--red);font-size:12.5px;margin-top:6px}

  .pwd-wrap{position:relative}
  .pwd-wrap .inp{padding-right:44px}
  .pwd-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9aa0b0;width:26px;height:26px;display:flex;align-items:center;justify-content:center;transition:color .15s}
  .pwd-toggle:hover{color:var(--blue)}
  .pwd-toggle svg{width:19px;height:19px}
  .pwd-toggle .eye-off{display:none}
  .pwd-toggle.show .eye-on{display:none}
  .pwd-toggle.show .eye-off{display:block}

  .remember{display:flex;align-items:center;gap:9px;font-size:13.5px;color:#555;margin:2px 0 16px;cursor:pointer;user-select:none}
  .remember input{width:16px;height:16px;accent-color:var(--blue)}

  .btn-submit{width:100%;padding:15px;border-radius:14px;background:linear-gradient(135deg,var(--red),var(--red-deep));color:#fff;font-family:'Poppins',sans-serif;font-weight:800;font-size:14px;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;border:none;transition:transform .15s, box-shadow .25s;box-shadow:0 12px 26px rgba(229,57,53,.34);margin-top:6px}
  .btn-submit:hover{transform:translateY(-2px);box-shadow:0 16px 34px rgba(229,57,53,.44)}
  .btn-submit:active{transform:translateY(0)}
  .auth-alt{text-align:center;margin-top:20px;font-size:14px;color:#666}
  .auth-alt a{color:var(--red);font-weight:800}
  .auth-foot{text-align:center;margin-top:22px;font-size:11.5px;color:#aeb3c0}

  .alert-ok{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647;padding:12px 14px;border-radius:11px;font-size:13.5px;margin-bottom:18px}
  .alert-err{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318;padding:12px 14px;border-radius:11px;font-size:13.5px;margin-bottom:18px}
  .pwd-reqs{background:#f7f8fb;border:1px solid #ECEFF5;border-radius:12px;padding:14px 16px;margin:4px 0 18px}
  .pwd-reqs .h{font-weight:700;font-size:12.5px;margin-bottom:8px}
  .pwd-reqs ul{list-style:none;display:flex;flex-direction:column;gap:5px}
  .pwd-reqs li{font-size:12.5px;color:#777;display:flex;gap:8px;align-items:center}
  .pwd-reqs li::before{content:'✓';color:#16a34a;font-weight:800}

  @keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
  @keyframes sheet{from{opacity:0;transform:translateY(40px)}to{opacity:1;transform:none}}
  @keyframes drift{from{transform:scale(1.06) translateY(0)}to{transform:scale(1.12) translateY(-12px)}}
  @keyframes march{to{stroke-dashoffset:-400}}
  @media (prefers-reduced-motion:reduce){*{animation:none!important}}

  /* ===== Desktop: panel visual kiri, form kanan ===== */
  @media (min-width:900px){
    .auth{flex-direction:row}
    .hero{flex:1.1;align-items:flex-start;text-align:left;justify-content:space-between;padding:56px 60px 64px;min-height:100vh}
    .hero-logo{width:min(34vw,440px)}
    .hero-tag{margin-top:20px}
    .hero-tag::after{display:none}
    .hero-trail{bottom:0;height:150px;opacity:.6}
    .hero-copy{display:block;max-width:470px;animation:rise .8s .2s cubic-bezier(.2,.8,.2,1) both}
    .hero-copy h2{font-weight:900;font-size:44px;line-height:1.02;letter-spacing:-.02em;margin-bottom:16px}
    .hero-copy h2 span{color:#FF6B5E}
    .hero-copy p{font-size:14.5px;line-height:1.7;color:rgba(255,255,255,.85)}
    .hero-feats{display:flex;list-style:none;flex-direction:column;gap:12px;margin-top:24px}
    .hero-feats li{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:600}
    .hero-feats svg{width:22px;height:22px;color:#fff;background:var(--red);border-radius:50%;padding:5px;flex-shrink:0}
    .hero-bottom{margin-bottom:96px}

    .auth-panel{flex:1;margin-top:0;border-radius:0;box-shadow:none;align-items:center;background:#eef1f7;padding:40px}
    .auth-panel::before{display:none}
    .auth-form{background:#fff;border-radius:26px;box-shadow:0 34px 90px rgba(16,18,30,.16);padding:36px 34px 28px}
  }
</style>
@endverbatim
@stack('styles')
</head>
<body>
<div class="auth">
  <section class="hero">
    <div class="hero-photo" style="background-image:url('{{ asset('assets/gtr/gallery/ari.webp') }}')"></div>

    <div>
      <a class="hero-home" href="{{ route('gtr') }}" aria-label="Gerung Trail Run 2026">
        <picture>
          <source srcset="{{ asset('assets/runger-logo-white.webp') }}" type="image/webp">
          <img class="hero-logo" src="{{ asset('assets/runger-logo-white.png') }}" alt="Runger — Runners Gerung" width="1000" height="245">
        </picture>
        <div class="hero-tag">Gerung Trail Run 2026</div>
      </a>
    </div>

    <div class="hero-bottom">
      <div class="hero-copy">
        <h2>Lari Bareng,<br><span>Sehat Bareng.</span></h2>
        <p>Race trail perdana di Lombok Barat — menyusuri punggung Bukit Keteri, hutan rendah, dan jalur pedesaan Gerung. Buat akun untuk ikut pendaftaran lomba.</p>
      </div>
      <ul class="hero-feats">
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Pendaftaran 7K &amp; 15K</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Bayar mudah via QRIS</li>
        <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> E-ticket &amp; status real-time</li>
      </ul>
    </div>

    <svg class="hero-trail" viewBox="0 0 1000 150" preserveAspectRatio="none" aria-hidden="true">
      <defs>
        <linearGradient id="trailFill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#fff" stop-opacity=".22"/>
          <stop offset="1" stop-color="#fff" stop-opacity="0"/>
        </linearGradient>
      </defs>
      <path class="fill" d="M0 120 L80 112 L150 96 L220 104 L300 70 L360 82 L430 46 L500 58 L560 28 L620 52 L690 40 L760 78 L830 66 L900 98 L1000 90 L1000 150 L0 150 Z"/>
      <path class="line" d="M0 120 L80 112 L150 96 L220 104 L300 70 L360 82 L430 46 L500 58 L560 28 L620 52 L690 40 L760 78 L830 66 L900 98 L1000 90"/>
      <circle cx="560" cy="28" r="6"/>
    </svg>
  </section>

  <div class="auth-panel">
    <div class="auth-form">
      @yield('form')
      <div class="auth-foot">&copy; {{ date('Y') }} Runger · Runners Gerung</div>
    </div>
  </div>
</div>
@stack('scripts')
</body>
</html>
