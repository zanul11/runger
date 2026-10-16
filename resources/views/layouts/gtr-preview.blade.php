<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>@yield('title', 'Preview — GTR Admin')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500..900&family=Plus+Jakarta+Sans:wght@400..800&display=swap" rel="stylesheet">
<style>
  :root{--red:#E53935;--red-deep:#C62828;--red-soft:rgba(229,57,53,.12)}
  *{box-sizing:border-box;margin:0;padding:0;min-width:0}
  body{background:#EEF0F3;color:#101010;font-family:'Plus Jakarta Sans',sans-serif;-webkit-font-smoothing:antialiased}
  a{color:inherit;text-decoration:none}
  button{font-family:inherit}
  .tab-note{display:flex;align-items:center;gap:8px;font-size:13px;color:#8A8A8A;margin-top:14px}
  .tab-note::before{content:'';width:6px;height:6px;border-radius:50%;background:#D0D0D0;flex-shrink:0}
  .tab-img{width:100%;max-height:280px;object-fit:cover;border-radius:16px;display:block}
</style>
@stack('styles')
</head>
<body>
@yield('content')
@stack('scripts')
</body>
</html>
