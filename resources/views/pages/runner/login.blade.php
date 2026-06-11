@extends('layouts.runner-auth')
@section('title', 'Masuk — Gerung Trail Run')

@section('form')
<h1 class="auth-title">Masuk</h1>
<p class="auth-sub">Masuk ke akun peserta GTR kamu</p>

@if(session('success'))
  <div class="alert-ok">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('gtr.login.store') }}">
  @csrf

  <div class="fld">
    <label for="email">Email <span class="req">*</span></label>
    <input class="inp" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
    @error('email')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="fld">
    <label for="password">Password <span class="req">*</span></label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="password" name="password" placeholder="Password kamu" required>
      <button type="button" class="pwd-toggle" data-target="password" aria-label="Lihat password">👁</button>
    </div>
    @error('password')<div class="err">{{ $message }}</div>@enderror
  </div>

  <label style="display:flex;align-items:center;gap:8px;font-size:13.5px;color:#555;margin-bottom:8px;cursor:pointer">
    <input type="checkbox" name="remember" value="1"> Ingat saya
  </label>

  <button type="submit" class="btn-submit">Masuk</button>
</form>

<div class="auth-alt">Belum punya akun? <a href="{{ route('gtr.register') }}">Buat Akun</a></div>

@push('scripts')
@verbatim
<script>
  document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const inp = document.getElementById(btn.dataset.target);
      if(inp) inp.type = inp.type === 'password' ? 'text' : 'password';
    });
  });
</script>
@endverbatim
@endpush
@endsection
