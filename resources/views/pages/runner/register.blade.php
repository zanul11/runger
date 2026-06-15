@extends('layouts.runner-auth')
@section('title', 'Buat Akun — Gerung Trail Run')

@section('form')
<h1 class="auth-title">Buat Akun</h1>
<p class="auth-sub">Isi data kamu untuk membuat akun peserta GTR</p>

@if($errors->any())
  <div class="alert-err">Periksa kembali isian kamu — ada {{ $errors->count() }} yang perlu diperbaiki.</div>
@endif

<form method="POST" action="{{ route('gtr.register.store') }}" id="reg-form">
  @csrf

  <div class="row2">
    <div class="fld">
      <label for="first_name">Nama Depan <span class="req">*</span></label>
      <input class="inp" type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" placeholder="Nama depan" required>
      @error('first_name')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="fld">
      <label for="last_name">Nama Belakang</label>
      <input class="inp" type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" placeholder="Nama belakang">
      @error('last_name')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="fld">
    <label for="email">Email <span class="req">*</span></label>
    <input class="inp" type="email" id="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
    @error('email')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="fld">
    <label for="phone">No. HP / WhatsApp</label>
    <input class="inp" type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
    @error('phone')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="fld">
    <label for="address">Alamat</label>
    <input class="inp" type="text" id="address" name="address" value="{{ old('address') }}" placeholder="Alamat domisili">
    @error('address')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="row2">
    <div class="fld">
      <label for="birthdate">Tanggal Lahir <span class="req">*</span></label>
      <input class="inp" type="date" id="birthdate" name="birthdate" value="{{ old('birthdate') }}" required>
      @error('birthdate')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="fld">
      <label for="gender">Jenis Kelamin <span class="req">*</span></label>
      <select class="inp" id="gender" name="gender" required>
        <option value="" disabled @selected(!old('gender'))>Pilih</option>
        <option value="male" @selected(old('gender') === 'male')>Laki-laki</option>
        <option value="female" @selected(old('gender') === 'female')>Perempuan</option>
      </select>
      @error('gender')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="fld">
    <label for="password">Password <span class="req">*</span></label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="password" name="password" placeholder="Buat password" required>
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
      <input class="inp" type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password" required>
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

  <button type="submit" class="btn-submit">Buat Akun</button>
</form>

<div class="auth-alt">Sudah punya akun? <a href="{{ route('gtr.login') }}">Masuk</a></div>

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
