@extends('layouts.gtr')

@section('title', 'Results — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@section('content')
<section class="block" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Race Results</div>
      <h2>Results</h2>
      <p>Hasil resmi dan klasemen akhir akan diumumkan di sini setelah race day.</p>
    </div>
    <div class="placeholder">
      <div class="ph-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
        </svg>
      </div>
      <div class="ph-title">Race Belum Dimulai</div>
      <div class="ph-sub">Klasemen finisher, chip time, dan medali certificate akan tersedia online dalam 24 jam setelah race day.</div>
      <div class="ph-badge"><span class="pulse"></span>29 Nov 2026</div>
    </div>
  </div>
</section>
@endsection
