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
  .pw-sub{color:var(--soft);font-size:13px;margin:-6px 0 14px}
  .fld{margin-bottom:13px}
  .fld > label{display:block;font-weight:700;font-size:12.5px;margin-bottom:7px;color:var(--text)}
  .pwd-wrap{position:relative}
  .inp{width:100%;padding:12px 44px 12px 14px;border:1.5px solid var(--line);border-radius:12px;font-size:14px;font-family:inherit;background:#fbfcfe;color:var(--text)}
  .inp:focus{outline:none;border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(27,63,174,.1)}
  .pwd-toggle{position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--mute);cursor:pointer;padding:6px;display:flex}
  .pwd-toggle svg{width:18px;height:18px}
  .pwd-toggle .eye-off{display:none}
  .pwd-toggle.show .eye-on{display:none}
  .pwd-toggle.show .eye-off{display:block}
  .err{color:var(--red);font-size:12.5px;margin-top:6px}
  .pw-hint{color:var(--mute);font-size:12px;margin:0 0 14px}
  .btn-pw{width:100%;background:linear-gradient(135deg,var(--blue-bright),var(--blue));color:#fff;border:none;font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;letter-spacing:.04em;text-transform:uppercase;padding:14px;border-radius:12px;cursor:pointer}
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

<div class="card" id="ganti-password">
  <div class="card-h">Ganti Password</div>
  <p class="pw-sub">Masukkan password lama, lalu buat password baru.</p>
  <form method="POST" action="{{ route('gtr.account.password') }}">
  @csrf
  @method('PUT')
  <div class="fld">
    <label for="current_password">Password Lama</label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="current_password" name="current_password" placeholder="Password saat ini" autocomplete="current-password" required>
      <button type="button" class="pwd-toggle" data-target="current_password" aria-label="Lihat password">
        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
      </button>
    </div>
    @error('current_password', 'password')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div class="fld">
    <label for="password">Password Baru</label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="password" name="password" placeholder="Password baru" autocomplete="new-password" required>
      <button type="button" class="pwd-toggle" data-target="password" aria-label="Lihat password">
        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
      </button>
    </div>
    @error('password', 'password')<div class="err">{{ $message }}</div>@enderror
  </div>
  <div class="fld">
    <label for="password_confirmation">Konfirmasi Password Baru</label>
    <div class="pwd-wrap">
      <input class="inp" type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" autocomplete="new-password" required>
      <button type="button" class="pwd-toggle" data-target="password_confirmation" aria-label="Lihat password">
        <svg class="eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
      </button>
    </div>
  </div>
  <p class="pw-hint">Minimal 8 karakter, ada huruf besar &amp; kecil, dan angka.</p>
  <button type="submit" class="btn-pw">Simpan Password</button>
  </form>
</div>

<form method="POST" action="{{ route('gtr.logout') }}">
  @csrf
  <button type="submit" class="btn-logout">Keluar dari Akun</button>
</form>

@push('scripts')
@verbatim
<script>
  @endverbatim
  @if($errors->password->any())
  document.getElementById('ganti-password').scrollIntoView({block: 'start'});
  @endif
  @verbatim
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
