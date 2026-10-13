@extends('layouts.runner-auth')
@section('title', 'Buat Password Baru — Gerung Trail Run')

@section('form')
<div class="auth-eyebrow">Akun Peserta GTR</div>
<h1 class="auth-title">Password Baru 🔐</h1>
<p class="auth-sub">Buat password baru untuk akun peserta kamu</p>

<form method="POST" action="{{ route('gtr.password.update') }}">
  @csrf
  <input type="hidden" name="token" value="{{ $token }}">

  <div class="fld">
    <label for="email">Email <span class="req">*</span></label>
    <input class="inp" type="email" id="email" name="email" value="{{ old('email', $email) }}" placeholder="nama@email.com" required>
    @error('email')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="fld">
    <label for="password">Password Baru <span class="req">*</span></label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="password" name="password" placeholder="Buat password baru" required autofocus>
      <button type="button" class="pwd-toggle" data-target="password" aria-label="Lihat password">
        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
      </button>
    </div>
    @error('password')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="fld">
    <label for="password_confirmation">Konfirmasi Password <span class="req">*</span></label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" required>
      <button type="button" class="pwd-toggle" data-target="password_confirmation" aria-label="Lihat password">
        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
      </button>
    </div>
  </div>

  <div class="pwd-reqs">
    <div class="h">Syarat Password:</div>
    <ul>
      <li>Minimal 8 karakter</li>
      <li>Ada huruf besar &amp; kecil</li>
      <li>Ada angka</li>
    </ul>
  </div>

  <button type="submit" class="btn-submit">Simpan Password</button>
</form>

<div class="auth-alt">Link kedaluwarsa? <a href="{{ route('gtr.password.request') }}">Minta link baru</a></div>

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
