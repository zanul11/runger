@extends('layouts.gtr')

@section('title', 'Entry List — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@section('content')
<section class="block" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Registered Runners</div>
      <h2>Entry List</h2>
      <p>Daftar peserta yang sudah terdaftar akan ditampilkan di sini setelah pendaftaran resmi dibuka.</p>
    </div>
    <div class="placeholder">
      <div class="ph-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
        </svg>
      </div>
      <div class="ph-title">Belum Ada Peserta Terdaftar</div>
      <div class="ph-sub">Pendaftaran resmi belum dibuka. Entry list akan diperbarui real-time setelah registrasi launch.</div>
      <div class="ph-badge"><span class="pulse"></span>Opening Soon</div>
    </div>
  </div>
</section>
@endsection
