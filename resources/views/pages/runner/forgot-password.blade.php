@extends('layouts.runner-auth')
@section('title', 'Lupa Password — Gerung Trail Run')

@section('form')
<div class="auth-eyebrow">Akun Peserta GTR</div>
<h1 class="auth-title">Lupa Password 🔑</h1>
<p class="auth-sub">Masukkan email akun kamu, kami kirim link untuk membuat password baru</p>

@if(session('success'))
  <div class="alert-ok">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('gtr.password.email') }}">
  @csrf

  <div class="fld">
    <label for="email">Email <span class="req">*</span></label>
    <input class="inp" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
    @error('email')<div class="err">{{ $message }}</div>@enderror
  </div>

  <button type="submit" class="btn-submit">Kirim Link Reset</button>
</form>

<div class="auth-alt">Sudah ingat password? <a href="{{ route('gtr.login') }}">Masuk</a></div>
@endsection
