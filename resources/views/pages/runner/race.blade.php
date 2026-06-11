@extends('layouts.runner-app')
@section('title', 'My Race — Akun Peserta GTR')
@section('app-title', 'My Race')

@push('styles')
@verbatim
<style>
  .race-card{display:flex;gap:14px;align-items:flex-start}
  .race-gtr{height:46px;width:46px;border-radius:10px;object-fit:cover;flex-shrink:0;box-shadow:0 4px 12px rgba(0,0,0,.4)}
  .race-card .nm{font-family:'Archivo',sans-serif;font-weight:800;font-size:18px;line-height:1.1}
  .race-card .mt{font-size:12px;color:var(--mute);margin-top:6px}
  .race-card .head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px}
</style>
@endverbatim
@endpush

@section('content')
<div class="sec-title">My Race</div>
<div class="sec-sub">Daftar lomba Gerung Trail Run yang kamu ikuti.</div>

@forelse($registrations as $reg)
  <a class="card" href="{{ route('gtr.account.race.detail', $reg) }}" style="display:block">
    <div class="race-card">
      <img class="race-gtr" src="{{ asset('assets/gtr/logo.jpeg') }}" alt="GTR">
      <div style="flex:1">
        <div class="head">
          <div class="nm">{{ $reg->category->distance ?? '' }} · {{ $reg->category->name ?? '-' }}</div>
          <span class="badge {{ $reg->payment_status }}">{{ ucfirst($reg->payment_status) }}</span>
        </div>
        <div class="mt">No. Reg: {{ $reg->nomor_registrasi ?: '-' }}</div>
        <div class="mt">BIB: {{ $reg->bib_number ?: 'Belum diterbitkan' }}</div>
        <div class="mt" style="color:var(--blue-bright);font-weight:700;margin-top:8px">Lihat detail &amp; QR →</div>
      </div>
    </div>
  </a>
@empty
  <div class="card"><div class="empty">Belum ada lomba. Daftar dulu di tab Home.</div></div>
@endforelse
@endsection
