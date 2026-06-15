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
  :root{--red:#E53935;--red-deep:#C62828;--ink:#101010;--line:#DADADA}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Plus Jakarta Sans',sans-serif;background:#fff;color:var(--ink);-webkit-font-smoothing:antialiased}
  a{color:inherit;text-decoration:none}
  .auth{display:flex;min-height:100vh}
  .auth-visual{display:none}
  .auth-panel{flex:1;display:flex;align-items:flex-start;justify-content:center;padding:40px 20px}
  .auth-form{width:100%;max-width:460px;padding:8px 0 40px}
  .auth-brand{display:flex;justify-content:center;margin-bottom:22px}
  .auth-brand img{height:40px;width:auto}
  .auth-title{font-family:'Archivo',sans-serif;font-weight:800;font-size:28px;text-align:center;letter-spacing:-.01em}
  .auth-sub{text-align:center;color:#888;margin:8px 0 28px;font-size:14px}
  .fld{margin-bottom:16px}
  .fld > label{display:block;font-weight:600;font-size:13px;margin-bottom:7px}
  .fld .req{color:var(--red)}
  .inp{width:100%;padding:13px 14px;border:1px solid var(--line);border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:var(--ink);transition:border-color .15s, box-shadow .15s}
  .inp:focus{outline:none;border-color:var(--red);box-shadow:0 0 0 3px rgba(229,57,53,.12)}
  select.inp{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center}
  .row2{display:contents}
  .phone-row{display:grid;grid-template-columns:118px 1fr;gap:10px}
  .err{color:var(--red);font-size:12.5px;margin-top:6px}
  .pwd-wrap{position:relative}
  .pwd-wrap .inp{padding-right:42px}
  .pwd-toggle{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#888;width:26px;height:26px;display:flex;align-items:center;justify-content:center}
  .pwd-toggle:hover{color:var(--ink)}
  .pwd-toggle svg{width:18px;height:18px}
  .pwd-toggle .eye-off{display:none}
  .pwd-toggle.show .eye-on{display:none}
  .pwd-toggle.show .eye-off{display:block}
  .btn-submit{width:100%;padding:15px;border-radius:10px;background:var(--ink);color:#fff;font-family:'Archivo',sans-serif;font-weight:700;font-size:15px;cursor:pointer;border:none;transition:background .2s, transform .15s;margin-top:8px}
  .btn-submit:hover{background:#000;transform:translateY(-1px)}
  .auth-alt{text-align:center;margin-top:20px;font-size:14px;color:#666}
  .auth-alt a{color:var(--red);font-weight:700}
  .auth-foot{text-align:center;margin-top:26px;font-size:11.5px;color:#aaa}
  .alert-ok{background:#ECFDF3;border:1px solid #ABEFC6;color:#067647;padding:12px 14px;border-radius:10px;font-size:13.5px;margin-bottom:18px}
  .alert-err{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318;padding:12px 14px;border-radius:10px;font-size:13.5px;margin-bottom:18px}
  .pwd-reqs{background:#F7F7F7;border:1px solid #ECECEC;border-radius:10px;padding:14px 16px;margin:6px 0 18px}
  .pwd-reqs .h{font-weight:700;font-size:12.5px;margin-bottom:8px}
  .pwd-reqs ul{list-style:none;display:flex;flex-direction:column;gap:5px}
  .pwd-reqs li{font-size:12.5px;color:#777;display:flex;gap:8px;align-items:center}
  .pwd-reqs li::before{content:'✓';color:#16a34a}

  @media (min-width:900px){
    .auth-visual{
      display:block;flex:1;position:relative;background-size:cover;background-position:center;color:#fff;
    }
    .auth-visual::before{content:'';position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.35),rgba(0,0,0,.82))}
    .auth-visual-inner{position:absolute;inset:0;z-index:1;display:flex;flex-direction:column;justify-content:space-between;padding:48px 44px}
    .auth-logo{height:64px;width:auto}
    .auth-visual h2{font-family:'Archivo',sans-serif;font-weight:900;font-size:34px;line-height:1.05;margin-bottom:16px}
    .auth-visual p{font-size:14.5px;line-height:1.6;opacity:.9;max-width:440px}
    .auth-visual .tagline{font-family:'Archivo',sans-serif;font-weight:800;font-size:18px;letter-spacing:.02em;margin-top:18px}
    .auth-panel{flex:1;align-items:center;padding:40px}
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
        <h2>Selamat Datang di<br>Gerung Trail Run 2026.</h2>
        <p>Race trail perdana di Lombok Barat — menyusuri punggung Bukit Keteri, hutan rendah, dan jalur pedesaan Gerung. Daftar akun untuk mengikuti pendaftaran lomba.</p>
        <div class="tagline">GTR 2026 — Bukit Keteri Trail</div>
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
