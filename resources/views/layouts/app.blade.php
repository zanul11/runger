<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>@yield('title', config('app.name', 'Runger'))</title>
<meta name="description" content="@yield('description', 'Runners Gerung — komunitas lari Lombok Barat. Lari Bareng, Sehat Bareng.')">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&family=Archivo:wght@400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@stack('head')
<style>
  :root{
    --runger-blue:#1B3FAE;
    --runger-blue-deep:#0F2680;
    --ink:#0A0F2C;
    --bone:#F4F1EA;
    --paper:#FAF8F3;
    --volt:oklch(0.88 0.18 110);
    --volt-rgb:226 240 84;
    --line:rgba(255,255,255,.1);
    --line-strong:rgba(255,255,255,.2);
    --red:#E83A2C;
  }
  *{box-sizing:border-box;margin:0;padding:0;min-width:0}
  html,body{background:var(--ink);color:var(--bone);font-family:'Inter',sans-serif;-webkit-font-smoothing:antialiased;overflow-x:hidden}
  a{color:inherit;text-decoration:none}
  img{display:block;max-width:100%}
  button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}
  .wrap{max-width:1320px;margin:0 auto;padding:0 16px;width:100%}
  @media (min-width:820px){
    .wrap{padding:0 24px}
  }
</style>
@stack('styles')
</head>
<body>
@yield('body')
@stack('scripts')
</body>
</html>
