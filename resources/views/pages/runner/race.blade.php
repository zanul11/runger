@extends('layouts.runner-app')
@section('title', 'Race — Runger')

@section('content')
<div class="sec-title">Race</div>
<div class="sec-sub">BIB, rute, race plan &amp; race pack kamu ada di sini.</div>

<div class="card">
  <div class="empty">
    @if($closed)
      Kamu belum terdaftar di Gerung Trail Run 2026, dan pendaftaran sudah ditutup.<br>
      <a href="{{ route('gtr') }}" style="color:var(--blue);font-weight:700">Lihat info event di website →</a>
    @else
      Kamu belum terdaftar di race mana pun.<br>
      <a href="{{ route('gtr.dashboard') }}" style="color:var(--blue);font-weight:700">Pilih kategori di Home →</a>
    @endif
  </div>
</div>
@endsection
