@extends('layouts.app')
@section('title', 'Volunteer GTR — ' . \App\Models\Setting::get('site.name'))
@section('description', 'Daftar jadi volunteer / panitia acara GTR bareng Runners Gerung.')

@push('styles')
<style>
  .ph{
    position:relative;overflow:hidden;padding:56px 16px 44px;
    border-bottom:1px solid rgba(255,255,255,.1);
    background:
      radial-gradient(880px 460px at 82% -20%, rgba(var(--volt-rgb),.18), transparent 60%),
      radial-gradient(720px 440px at 0% 120%, rgba(27,63,174,.55), transparent 62%),
      linear-gradient(180deg, var(--runger-blue-deep), var(--ink));
  }
  .ph::before{
    content:'';position:absolute;inset:0;pointer-events:none;
    background-image:
      linear-gradient(transparent 96%, rgba(255,255,255,.05) 96%),
      linear-gradient(90deg, transparent 96%, rgba(255,255,255,.05) 96%);
    background-size:42px 42px;
    -webkit-mask:radial-gradient(circle at 50% 36%, #000 28%, transparent 74%);
            mask:radial-gradient(circle at 50% 36%, #000 28%, transparent 74%);
  }
  .ph::after{
    content:'VOLUNTEER';position:absolute;left:50%;bottom:-24px;transform:translateX(-50%);
    font-family:'Bebas Neue',sans-serif;font-size:clamp(82px,17vw,190px);line-height:.8;white-space:nowrap;
    color:rgba(255,255,255,.045);letter-spacing:.02em;pointer-events:none;z-index:0;
  }
  .ph-inner{position:relative;z-index:1;max-width:760px;margin:0 auto;display:flex;flex-direction:column;align-items:center;text-align:center;gap:18px}
  .ph-inner > *{animation:phUp .6s cubic-bezier(.22,1,.36,1) both}
  .ph-inner > *:nth-child(2){animation-delay:.08s}
  .ph-inner > *:nth-child(3){animation-delay:.16s}
  .ph-inner > *:nth-child(4){animation-delay:.24s}
  .ph-inner > *:nth-child(5){animation-delay:.32s}
  @keyframes phUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}

  .ph-logo-badge{
    display:inline-flex;align-items:center;justify-content:center;
    background:#fff;border-radius:22px;padding:16px 24px;
    border:1px solid rgba(255,255,255,.7);
    box-shadow:0 22px 55px rgba(0,0,0,.45), 0 0 0 6px rgba(255,255,255,.06);
    transition:transform .3s ease;
  }
  .ph-logo-badge:hover{transform:translateY(-3px) rotate(-1deg)}
  .ph-logo{width:auto;max-width:200px;height:auto;display:block}

  .ph-pill{
    display:inline-flex;align-items:center;gap:8px;padding:7px 15px;border-radius:999px;
    background:rgba(var(--volt-rgb),.12);border:1px solid rgba(var(--volt-rgb),.4);color:var(--volt);
    font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.18em;text-transform:uppercase;font-weight:600;
  }
  .ph-pill .dot{width:7px;height:7px;border-radius:50%;background:var(--volt);animation:phPulse 1.6s infinite}
  @keyframes phPulse{0%,100%{opacity:1;box-shadow:0 0 0 0 rgba(var(--volt-rgb),.5)}70%{box-shadow:0 0 0 7px rgba(var(--volt-rgb),0)}}

  .ph-title{font-family:'Bebas Neue',sans-serif;font-size:52px;line-height:.92}
  .ph-title em{font-style:normal;color:var(--volt)}
  .ph-sub{margin:0 auto;font-size:14px;line-height:1.6;opacity:.8;max-width:520px}
  .ph-sub strong{color:var(--volt);font-weight:600}
  .ph-tags{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:2px}
  .ph-tag{
    font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:.12em;text-transform:uppercase;
    padding:8px 13px;border:1px solid rgba(255,255,255,.2);border-radius:999px;opacity:.85;transition:border-color .15s,color .15s,opacity .15s;
  }
  .ph-tag:hover{border-color:rgba(var(--volt-rgb),.5);color:var(--volt);opacity:1}

  .vol-wrap{max-width:760px;margin:0 auto;padding:28px 16px 72px}
  .vol-alert{padding:16px 18px;border-radius:6px;background:rgba(226,240,84,.1);border:1px solid rgba(226,240,84,.4);color:var(--volt);font-size:14px;margin-bottom:24px;line-height:1.5}
  .vol-form{display:flex;flex-direction:column;gap:22px}
  .fld{display:flex;flex-direction:column;gap:8px}
  .fld > label{font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.16em;text-transform:uppercase;opacity:.85}
  .fld > label .req{color:var(--volt)}
  .fld .hint{font-family:'JetBrains Mono',monospace;font-size:10px;letter-spacing:.08em;opacity:.5;text-transform:none}
  .fld input[type=text],.fld input[type=tel],.fld textarea{
    width:100%;background:rgba(255,255,255,.03);border:1px solid var(--line-strong);border-radius:6px;
    color:var(--bone);font-family:'Inter',sans-serif;font-size:15px;padding:13px 14px;transition:border-color .15s,background .15s
  }
  .fld input:focus,.fld textarea:focus{outline:none;border-color:var(--volt);background:rgba(255,255,255,.05)}
  .fld textarea{resize:vertical;min-height:96px;line-height:1.5}
  .fld input::placeholder,.fld textarea::placeholder{color:rgba(244,241,234,.35)}
  .fld .err{color:var(--red);font-size:12.5px}

  .chips{display:grid;grid-template-columns:repeat(2,1fr);gap:10px}
  .chip{position:relative;display:flex;align-items:center;gap:10px;padding:14px 16px;border:1px solid var(--line-strong);border-radius:6px;cursor:pointer;transition:border-color .15s,background .15s;user-select:none}
  .chip:hover{border-color:rgba(226,240,84,.4)}
  .chip input{position:absolute;opacity:0;pointer-events:none}
  .chip .box{width:18px;height:18px;border-radius:4px;border:1.5px solid rgba(255,255,255,.4);flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;transition:all .15s}
  .chip .box::after{content:'';width:9px;height:9px;border-radius:2px;background:var(--volt);transform:scale(0);transition:transform .12s}
  .chip .txt{font-size:14px;font-weight:500}
  .chip.checked{border-color:var(--volt);background:rgba(226,240,84,.08)}
  .chip.checked .box{border-color:var(--volt)}
  .chip.checked .box::after{transform:scale(1)}
  .chip.disabled{opacity:.4;cursor:not-allowed}
  .chip.disabled:hover{border-color:var(--line-strong)}

  .vol-submit{display:inline-flex;align-items:center;justify-content:center;gap:8px;align-self:flex-start;padding:14px 30px;border-radius:999px;background:var(--volt);color:var(--ink);font-weight:700;font-size:14px;letter-spacing:.04em;text-transform:uppercase;transition:transform .15s,opacity .2s}
  .vol-submit:hover{transform:translateY(-1px)}

  @media (min-width:820px){
    .ph{padding:72px 32px 56px}
    .ph-title{font-size:72px}
    .ph-logo{max-width:240px}
    .vol-wrap{padding:36px 32px 90px}
  }
  @media (prefers-reduced-motion:reduce){
    .ph-inner > *{animation:none}
    .ph-pill .dot{animation:none}
  }
</style>
@endpush

@section('body')
<x-site-nav active="volunteer" />

<header class="ph">
  <div class="ph-inner">
    <span class="ph-logo-badge">
      <img class="ph-logo" src="{{ asset('gtr/logo-gtr2.png') }}" alt="GTR — Gerung Trail Run">
    </span>
    <span class="ph-pill"><span class="dot"></span>Open Recruitment</span>
    <h1 class="ph-title">VOLUNTEER <em>GTR</em></h1>
    <p class="ph-sub">Gabung jadi volunteer acara <strong>GTR — Gerung Trail Run</strong>. Pilih bidang yang kamu minati, isi datanya, sisanya tim kami yang hubungi kamu.</p>
    <div class="ph-tags">
      @foreach(\App\Models\Volunteer::INTERESTS as $label)
        <span class="ph-tag">{{ $label }}</span>
      @endforeach
    </div>
  </div>
</header>

<section class="vol-wrap">
  @if(session('success'))
    <div class="vol-alert">{{ session('success') }}</div>
  @endif

  <form class="vol-form" method="POST" action="{{ route('volunteer.store') }}">
    @csrf

    <div class="fld">
      <label for="name">Nama Lengkap <span class="req">*</span></label>
      <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Nama kamu" required>
      @error('name')<span class="err">{{ $message }}</span>@enderror
    </div>

    <div class="fld">
      <label for="phone">No. HP / WhatsApp <span class="req">*</span></label>
      <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>
      @error('phone')<span class="err">{{ $message }}</span>@enderror
    </div>

    <div class="fld">
      <label for="address">Alamat <span class="req">*</span></label>
      <textarea id="address" name="address" placeholder="Alamat domisili kamu" required>{{ old('address') }}</textarea>
      @error('address')<span class="err">{{ $message }}</span>@enderror
    </div>

    <div class="fld">
      <label>Minat Kepanitiaan <span class="req">*</span> <span class="hint">— pilih maksimal 2</span></label>
      <div class="chips" id="vol-chips" data-max="2">
        @php $picked = (array) old('interests', []); @endphp
        @foreach(\App\Models\Volunteer::INTERESTS as $key => $label)
          <label class="chip {{ in_array($key, $picked) ? 'checked' : '' }}">
            <input type="checkbox" name="interests[]" value="{{ $key }}" {{ in_array($key, $picked) ? 'checked' : '' }}>
            <span class="box"></span>
            <span class="txt">{{ $label }}</span>
          </label>
        @endforeach
      </div>
      @error('interests')<span class="err">{{ $message }}</span>@enderror
    </div>

    <div class="fld">
      <label for="experience">Pengalaman Jadi Panitia / Volunteer <span class="hint">— opsional</span></label>
      <textarea id="experience" name="experience" placeholder="Ceritakan pengalaman kamu jadi panitia/volunteer sebelumnya (kosongkan kalau belum ada)">{{ old('experience') }}</textarea>
      @error('experience')<span class="err">{{ $message }}</span>@enderror
    </div>

    <div class="fld">
      <label for="skills">Keahlian <span class="hint">— opsional</span></label>
      <input type="text" id="skills" name="skills" value="{{ old('skills') }}" placeholder="Contoh: desain grafis, fotografi, MC, P3K, sosmed">
      @error('skills')<span class="err">{{ $message }}</span>@enderror
    </div>

    <div class="fld">
      <label for="reason">Alasan Ingin Jadi Volunteer <span class="req">*</span></label>
      <textarea id="reason" name="reason" placeholder="Ceritakan kenapa kamu mau gabung jadi volunteer GTR" required>{{ old('reason') }}</textarea>
      @error('reason')<span class="err">{{ $message }}</span>@enderror
    </div>

    <button type="submit" class="vol-submit">Kirim Pendaftaran →</button>
  </form>
</section>

<x-site-footer />

<script>
  (function(){
    const group = document.getElementById('vol-chips');
    if(!group) return;
    const max = parseInt(group.dataset.max, 10) || 2;
    const chips = Array.from(group.querySelectorAll('.chip'));
    const boxes = chips.map(c => c.querySelector('input'));

    function sync(){
      const checked = boxes.filter(b => b.checked).length;
      chips.forEach((chip, i) => {
        const b = boxes[i];
        chip.classList.toggle('checked', b.checked);
        const disable = !b.checked && checked >= max;
        chip.classList.toggle('disabled', disable);
        b.disabled = disable;
      });
    }
    boxes.forEach(b => b.addEventListener('change', sync));
    sync();
  })();
</script>
@endsection
