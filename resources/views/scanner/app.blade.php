<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#e9eae5">
    <meta name="color-scheme" content="light">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="GTR Crew">
    <meta name="application-name" content="GTR Crew">
    <meta name="robots" content="noindex, nofollow">
    <title>GTR Crew</title>
    {{-- Halaman ini di-precache service worker: jangan taruh data per-user / CSRF di sini. --}}
    <link rel="manifest" href="/crew/app.webmanifest">
    <link rel="apple-touch-icon" href="/pwa/gtr-scanner-apple-touch.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/pwa/gtr-scanner-192.png">
    @vite('resources/js/scanner/main.js')
</head>
<body>
    <div id="app"></div>
    <noscript>GTR Crew butuh JavaScript.</noscript>
</body>
</html>
