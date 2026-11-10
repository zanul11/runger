<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <meta name="color-scheme" content="light">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="robots" content="noindex, nofollow">
    <title>GTR Scanner</title>
    {{-- Halaman ini di-precache service worker: jangan taruh data per-user / CSRF di sini. --}}
    <link rel="manifest" href="/scanner/scanner.webmanifest">
    <link rel="apple-touch-icon" href="/pwa/apple-touch-icon.png">
    @vite('resources/js/scanner/main.js')
</head>
<body>
    <div id="app"></div>
    <noscript>Scanner butuh JavaScript.</noscript>
</body>
</html>
