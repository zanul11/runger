<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>@yield('title', 'Gerung Trail Run — Akun Peserta')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@verbatim
<style>
  :root{--red:#E53935;--red-deep:#C62828;--ink:#0F1115;--line:#E4E7EF;--muted:#8A90A2}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#eceff5;color:var(--ink);-webkit-font-smoothing:antialiased}
  a{color:inherit;text-decoration:none}
  .auth{display:flex;min-height:100vh}

  /* ===== Form panel ===== */
  .auth-visual{display:none}
  .auth-panel{flex:1;display:flex;align-items:center;justify-content:center;padding:30px 16px}
  .auth-form{
    width:100%;max-width:444px;background:#fff;
    border-radius:24px;box-shadow:0 26px 70px rgba(16,18,30,.13);
    padding:32px 30px 26px;
  }
  .auth-brand{display:flex;justify-content:center;margin-bottom:16px}
  .auth-brand img{height:38px;width:auto}
  .auth-eyebrow{
    text-align:center;font-family:'Archivo',sans-serif;font-weight:800;
    font-size:11px;letter-spacing:.18em;text-transform:uppercase;color:var(--red);margin-bottom:7px;
  }
  .auth-title{font-family:'Archivo',sans-serif;font-weight:900;font-size:30px;text-align:center;letter-spacing:-.02em;line-height:1}
  .auth-sub{text-align:center;color:var(--muted);margin:9px 0 24px;font-size:14px}

  .fld{margin-bottom:15px}
  .fld > label{display:block;font-weight:700;font-size:12.5px;margin-bottom:7px;color:#2b2f3a}
  .fld .req{color:var(--red)}
  .inp{width:100%;padding:13px 15px;border:1.5px solid var(--line);border-radius:12px;font-size:14px;font-family:inherit;background:#fbfcfe;color:var(--ink);transition:border-color .15s, box-shadow .15s, background .15s}
  .inp::placeholder{color:#b3b8c6}
  .inp:focus{outline:none;border-color:var(--red);background:#fff;box-shadow:0 0 0 4px rgba(229,57,53,.1)}
  select.inp{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
  .row2{display:contents}
  .phone-row{display:grid;grid-template-columns:118px 1fr;gap:10px}
  .err{color:var(--red);font-size:12.5px;margin-top:6px}

  .pwd-wrap{position:relative}
  .pwd-wrap .inp{padding-right:44px}
  .pwd-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9aa0b0;width:26px;height:26px;display:flex;align-items:center;justify-content:center;transition:color .15s}
  .pwd-toggle:hover{color:var(--red)}
  .pwd-toggle svg{width:19px;height:19px}
  .pwd-toggle .eye-off{display:none}
  .pwd-toggle.show .eye-on{display:none}
  .pwd-toggle.show .eye-off{display:block}

  .remember{display:flex;align-items:center;gap:9px;font-size:13.5px;color:#555;margin:2px 0 16px;cursor:pointer;user-select:none}
  .remember input{width:16px;height:16px;accent-color:var(--red)}

  .btn-submit{width:100%;padding:15px;border-radius:12px;background:linear-gradient(135deg,var(--red),var(--red-deep));color:#fff;font-family:'Archivo',sans-serif;font-weight:800;font-size:14px;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;border:none;transition:transform .15s, box-shadow .25s;box-shadow:0 10px 24px rgba(229,57,53,.32);margin-top:6px}
  .btn-submit:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(229,57,53,.42)}
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

  /* ===== Visual panel (desktop) ===== */
  @media (min-width:900px){
    .auth-visual{
      display:block;flex:1.05;position:relative;background-size:cover;background-position:center;color:#fff;
    }
    .auth-visual::before{content:'';position:absolute;inset:0;background:linear-gradient(155deg,rgba(197,40,40,.34) 0%,rgba(0,0,0,.5) 42%,rgba(0,0,0,.92) 100%)}
    .auth-visual-inner{position:absolute;inset:0;z-index:1;display:flex;flex-direction:column;justify-content:space-between;padding:54px 52px}
    .auth-logo{width:150px;height:auto;max-width:55%;object-fit:contain;display:block}
    .v-eyebrow{font-family:'Archivo',sans-serif;font-weight:800;font-size:12px;letter-spacing:.22em;text-transform:uppercase;opacity:.92;margin-bottom:14px;display:inline-flex;align-items:center;gap:10px}
    .v-eyebrow::before{content:'';width:28px;height:2px;background:var(--red)}
    .auth-visual h2{font-family:'Archivo',sans-serif;font-weight:900;font-size:42px;line-height:1.02;margin-bottom:18px;letter-spacing:-.02em}
    .auth-visual p{font-size:14.5px;line-height:1.65;opacity:.9;max-width:450px}
    .v-feats{list-style:none;margin-top:24px;display:flex;flex-direction:column;gap:12px}
    .v-feats li{display:flex;align-items:center;gap:12px;font-size:14px;font-weight:600}
    .v-feats svg{width:20px;height:20px;color:#fff;background:var(--red);border-radius:50%;padding:4px;flex-shrink:0}
    .auth-panel{flex:1;align-items:center;padding:40px}
    .auth-form{box-shadow:0 34px 90px rgba(16,18,30,.16)}
  }
</style>
@endverbatim
@stack('styles')
</head>
<body>
<div class="auth">
  <div class="auth-visual" style="background-image:url('{{ asset('assets/gtr/gallery/ari.jpeg') }}')">
    <div class="auth-visual-inner">
      <img class="auth-logo" src="{{ asset('assets/gtr/logo-white.png') }}" alt="Gerung Trail Run">
      <div>
        <div class="v-eyebrow">Gerung Trail Run 2026</div>
        <h2>Lari Bareng,<br>Sehat Bareng.</h2>
        <p>Race trail perdana di Lombok Barat — menyusuri punggung Bukit Keteri, hutan rendah, dan jalur pedesaan Gerung. Buat akun untuk ikut pendaftaran lomba.</p>
        <ul class="v-feats">
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Pendaftaran 7K &amp; 15K</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Bayar mudah via QRIS</li>
          <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> E-ticket &amp; status real-time</li>
        </ul>
      </div>
    </div>
  </div>
  <div class="auth-panel">
    <div class="auth-form">
      <div class="auth-brand"><img src="{{ asset('assets/runger-logo.png') }}" alt="Runger"></div>
      @yield('form')
      <div class="auth-foot">&copy; {{ date('Y') }} Runger · Gerung Trail Run</div>
    </div>
  </div>
</div>
@stack('scripts')
</body>
</html>
