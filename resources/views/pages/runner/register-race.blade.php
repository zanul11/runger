@extends('layouts.runner-app')
@section('title', 'Pendaftaran ' . $category->distance . ' — Runger')
@section('app-title', 'Pendaftaran Race')

@push('styles')
@verbatim
<style>
  .rf-hero{display:flex;align-items:center;gap:13px;background:var(--card);border:1px solid var(--line);border-radius:16px;padding:14px 16px;margin-bottom:18px;box-shadow:0 6px 20px rgba(15,24,48,.05)}
  .rf-hero img{height:42px;width:42px;border-radius:9px;object-fit:cover}
  .rf-hero .d{font-family:'Archivo',sans-serif;font-weight:900;font-size:22px;line-height:1;color:var(--text)}
  .rf-hero .n{font-size:12px;color:var(--soft);margin-top:3px}
  .rf-hero .pr{margin-left:auto;text-align:right}
  .rf-hero .pr .eb{font-family:'Archivo',sans-serif;font-weight:900;color:var(--blue);font-size:15px}
  .rf-hero .pr .lb{font-size:9.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--mute);font-weight:700}
  .rf-h{font-family:'Archivo',sans-serif;font-weight:800;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:var(--blue);margin:18px 2px 12px}
  .fld{margin-bottom:14px}
  .fld > label{display:block;font-weight:600;font-size:13px;margin-bottom:7px;color:#2A3350}
  .fld .req{color:var(--red)}
  .inp{width:100%;padding:12px 13px;border:1px solid #D7DDEA;border-radius:10px;font-size:14px;font-family:inherit;background:#fff;color:var(--text);transition:border-color .15s, box-shadow .15s}
  .inp::placeholder{color:#A0A7BA}
  .inp:focus{outline:none;border-color:var(--blue);box-shadow:0 0 0 3px rgba(27,63,174,.14)}
  .row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .err{color:#C0392B;font-size:12px;margin-top:5px}
  .agree{display:flex;gap:10px;align-items:flex-start;background:#F4F7FD;border:1px solid var(--line);border-radius:12px;padding:14px;margin:6px 0 16px}
  .agree input{margin-top:3px;width:18px;height:18px;accent-color:var(--blue)}
  .agree label{font-size:12.5px;color:var(--soft);line-height:1.5}
  .agree strong{color:var(--text) !important}
  .rf-submit{width:100%;background:var(--blue);color:#fff;font-family:'Archivo',sans-serif;font-weight:700;font-size:14px;letter-spacing:.04em;text-transform:uppercase;padding:15px;border-radius:12px;border:none;cursor:pointer;transition:background .2s,transform .15s}
  .rf-submit:hover{background:var(--blue-deep);transform:translateY(-1px)}
  .alert-err{background:#FEF3F2;border:1px solid #FECDCA;color:#B42318;padding:12px 14px;border-radius:12px;font-size:13px;margin-bottom:16px}
</style>
@endverbatim
@endpush

@section('content')
@php
  $g = $runner->gender === 'male' ? 'Laki-laki' : ($runner->gender === 'female' ? 'Perempuan' : null);
@endphp

<a href="{{ route('gtr.dashboard') }}" style="display:inline-block;color:var(--soft);font-size:13px;font-weight:600;margin-bottom:14px">← Kembali</a>

<div class="rf-hero">
  <img src="{{ $category->header_url }}" alt="{{ $category->name }}">
  <div>
    <div class="d">{{ $category->distance }}</div>
    <div class="n">{{ $category->name }}</div>
  </div>
  <div class="pr">
    <div class="eb">{{ $category->early_bird_formatted }}</div>
    <div class="lb">Early Bird</div>
  </div>
</div>

@if($errors->any())
  <div class="alert-err">Ada {{ $errors->count() }} isian yang perlu diperbaiki.</div>
@endif

<form method="POST" action="{{ route('gtr.register.submit', $category) }}">
  @csrf

  <div class="rf-h">Data Peserta</div>

  <div class="fld">
    <label for="full_name">Nama Lengkap <span class="req">*</span></label>
    <input class="inp" id="full_name" name="full_name" value="{{ old('full_name', $runner->full_name) }}" placeholder="Sesuai KTP" required>
    @error('full_name')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="fld">
    <label for="nik">NIK (16 digit) <span class="req">*</span></label>
    <input class="inp" id="nik" name="nik" value="{{ old('nik') }}" inputmode="numeric" maxlength="16" placeholder="Nomor Induk Kependudukan" required>
    @error('nik')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="row2">
    <div class="fld">
      <label for="email">Email <span class="req">*</span></label>
      <input class="inp" type="email" id="email" name="email" value="{{ old('email', $runner->email) }}" required>
      @error('email')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="fld">
      <label for="whatsapp">WhatsApp <span class="req">*</span></label>
      <input class="inp" id="whatsapp" name="whatsapp" value="{{ old('whatsapp', $runner->phone) }}" placeholder="08xxxx" required>
      @error('whatsapp')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="row2">
    <div class="fld">
      <label for="birth_date">Tanggal Lahir <span class="req">*</span></label>
      <input class="inp" type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', optional($runner->birthdate)->toDateString()) }}" required>
      @error('birth_date')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="fld">
      <label for="gender">Jenis Kelamin <span class="req">*</span></label>
      <select class="inp" id="gender" name="gender" required>
        <option value="" disabled @selected(!old('gender', $g))>Pilih</option>
        @foreach(\App\Models\GtrRegistration::GENDERS as $opt)
          <option value="{{ $opt }}" @selected(old('gender', $g) === $opt)>{{ $opt }}</option>
        @endforeach
      </select>
      @error('gender')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="fld">
    <label for="address">Alamat <span class="req">*</span></label>
    <input class="inp" id="address" name="address" value="{{ old('address', $runner->address) }}" placeholder="Alamat domisili" required>
    @error('address')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="row2">
    <div class="fld">
      <label for="blood_type">Gol. Darah <span class="req">*</span></label>
      <select class="inp" id="blood_type" name="blood_type" required>
        <option value="" disabled @selected(!old('blood_type'))>Pilih</option>
        @foreach(\App\Models\GtrRegistration::BLOOD_TYPES as $opt)
          <option value="{{ $opt }}" @selected(old('blood_type') === $opt)>{{ $opt }}</option>
        @endforeach
      </select>
      @error('blood_type')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="fld">
      <label for="club">Klub / Komunitas</label>
      <input class="inp" id="club" name="club" value="{{ old('club') }}" placeholder="Opsional">
      @error('club')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="rf-h">Jersey & Kontak Darurat</div>

  <div class="fld">
    <label for="size">Ukuran Jersey <span class="req">*</span></label>
    <select class="inp" id="size" name="size" required>
      <option value="" disabled @selected(!old('size'))>Pilih ukuran</option>
      @foreach(\App\Models\GtrRegistration::SIZES as $opt)
        <option value="{{ $opt }}" @selected(old('size') === $opt)>{{ $opt }}</option>
      @endforeach
    </select>
    @error('size')<div class="err">{{ $message }}</div>@enderror
  </div>

  <div class="row2">
    <div class="fld">
      <label for="emergency_name">Nama Kontak Darurat <span class="req">*</span></label>
      <input class="inp" id="emergency_name" name="emergency_name" value="{{ old('emergency_name') }}" required>
      @error('emergency_name')<div class="err">{{ $message }}</div>@enderror
    </div>
    <div class="fld">
      <label for="emergency_contact">No. Kontak Darurat <span class="req">*</span></label>
      <input class="inp" id="emergency_contact" name="emergency_contact" value="{{ old('emergency_contact') }}" placeholder="08xxxx" required>
      @error('emergency_contact')<div class="err">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="rf-h">Pembayaran</div>

  <div class="fld">
    <label for="pay">Metode Pembayaran <span class="req">*</span></label>
    <select class="inp" id="pay" name="pay" required>
      @foreach(['Transfer Bank', 'QRIS', 'Virtual Account'] as $opt)
        <option value="{{ $opt }}" @selected(old('pay', 'Transfer Bank') === $opt)>{{ $opt }}</option>
      @endforeach
    </select>
    @error('pay')<div class="err">{{ $message }}</div>@enderror
  </div>

  <label class="agree">
    <input type="checkbox" name="agree_terms" value="1" {{ old('agree_terms') ? 'checked' : '' }}>
    <span>Saya menyatakan data di atas benar, dalam kondisi sehat, dan menyetujui <strong style="color:#fff">syarat &amp; ketentuan</strong> serta peraturan lomba Gerung Trail Run.</span>
  </label>
  @error('agree_terms')<div class="err" style="margin-top:-10px;margin-bottom:14px">{{ $message }}</div>@enderror

  <button type="submit" class="rf-submit">Daftar &amp; Lanjut Bayar</button>
</form>
@endsection
