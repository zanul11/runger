<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1f3d27">
<meta name="robots" content="noindex, nofollow">
<title>Roster Panitia{{ $event ? ' — '.$event->title : '' }} · GTR Crew</title>
<meta name="description" content="Roster panitia hari H (baca-saja): kebutuhan & petugas per pos, konsumsi Race Village.">
<link rel="icon" type="image/png" sizes="192x192" href="/pwa/gtr-scanner-192.png">
<link rel="apple-touch-icon" href="/pwa/gtr-scanner-apple-touch.png">
<link rel="preconnect" href="https://fonts.bunny.net">
{{--
  Halaman publik BACA-SAJA (tanpa login), seperti /crew/persiapan. Komponen yang sama dengan
  GTR Crew → Roster (resources/js/roster/RosterBoard.vue), data dari /crew/roster/data
  (no. HP disamarkan), diperbarui otomatis tiap 10 detik.
--}}
<style>html,body{margin:0;background:#f5f2ea}@media (prefers-color-scheme:dark){html,body{background:#101812}}</style>
@vite('resources/js/roster/main.js')
</head>
<body>
<div id="roster"></div>
<noscript>Halaman roster butuh JavaScript.</noscript>
</body>
</html>
