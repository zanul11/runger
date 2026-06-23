@extends('layouts.runner-auth')
@section('title', 'Masuk — Gerung Trail Run')

@section('form')
<div class="auth-eyebrow">Akun Peserta GTR</div>
<h1 class="auth-title">Selamat Datang 👋</h1>
<p class="auth-sub">Masuk ke akun peserta Gerung Trail Run kamu</p>

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
      <button type="button" class="pwd-toggle" data-target="password" aria-label="Lihat password">
        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
      </button>
    </div>
    @error('password')<div class="err">{{ $message }}</div>@enderror
  </div>

  <label class="remember">
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
      btn.classList.toggle('show');
    });
  });
</script>
@endverbatim
@endpush
@endsection
