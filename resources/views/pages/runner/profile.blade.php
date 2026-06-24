@extends('layouts.runner-app')
@section('title', 'Profile — Akun Peserta GTR')
@section('app-title', 'Profile')

@push('styles')
@verbatim
<style>
  .prof-top{display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;margin-bottom:18px}
  .prof-av{width:76px;height:76px;border-radius:50%;background:linear-gradient(135deg,#2A4FC8,#0F2680);display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-weight:900;font-size:30px;color:#fff;box-shadow:0 10px 26px rgba(27,63,174,.45)}
  .prof-name{font-family:'Poppins',sans-serif;font-weight:900;font-size:22px;line-height:1}
  .prof-email{color:var(--soft);font-size:13px}
  .pr-row{display:flex;justify-content:space-between;gap:14px;padding:13px 0;border-bottom:1px solid var(--line);font-size:14px}
  .pr-row:last-child{border-bottom:none}
  .pr-row .k{color:var(--mute)}
  .pr-row .v{font-weight:600;text-align:right}
  .btn-logout{width:100%;margin-top:16px;background:transparent;border:1px solid rgba(229,57,53,.5);color:var(--red);font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;letter-spacing:.04em;text-transform:uppercase;padding:14px;border-radius:12px;cursor:pointer;transition:background .2s}
  .btn-logout:hover{background:rgba(229,57,53,.1)}
</style>
@endverbatim
@endpush

@section('content')
<div class="prof-top">
  <div class="prof-av">{{ strtoupper(substr($runner->first_name, 0, 1)) }}</div>
  <div>
    <div class="prof-name">{{ $runner->full_name }}</div>
    <div class="prof-email">{{ $runner->email }}</div>
  </div>
</div>

<div class="card">
  <div class="card-h">Data Diri</div>
  <div class="pr-row"><span class="k">No. HP</span><span class="v">{{ $runner->phone ? $runner->phone_country . ' ' . $runner->phone : '-' }}</span></div>
  <div class="pr-row"><span class="k">Kewarganegaraan</span><span class="v">{{ $runner->nationality ?: '-' }}</span></div>
  <div class="pr-row"><span class="k">Tanggal Lahir</span><span class="v">{{ optional($runner->birthdate)->translatedFormat('d M Y') ?: '-' }}</span></div>
  <div class="pr-row"><span class="k">Jenis Kelamin</span><span class="v">{{ $runner->gender === 'male' ? 'Laki-laki' : ($runner->gender === 'female' ? 'Perempuan' : '-') }}</span></div>
  <div class="pr-row"><span class="k">Alamat</span><span class="v">{{ $runner->address ?: '-' }}</span></div>
</div>

<form method="POST" action="{{ route('gtr.logout') }}">
  @csrf
  <button type="submit" class="btn-logout">Keluar dari Akun</button>
</form>
@endsection
