@extends('layouts.gtr')

@php
  $dateText = $eventDate->locale('id')->translatedFormat('l, j F Y');
  $shareText = 'Ikut Road to GTR bareng yuk — fun trail + tanam pohon! ' . $dateText . '. Daftar di sini: ' . route('gtr.road');
@endphp

@section('title', 'Road to GTR · Fun Trail & Tanam Pohon — Gerung Trail Run 2026')
@section('description', 'Daftar Road to GTR, ' . $dateText . ' — fun trail & tanam pohon bareng sebelum Gerung Trail Run 2026.')
@section('og_image', asset('assets/gtr/gallery/rudi.jpeg'))
@section('bodyClass', 'gtr-sub')

{{--
  Mobile first: base CSS = layar HP (±360–430 px). Desktop hanya memperlebar hero & memusatkan form.
  - Input 16px → iOS Safari tidak auto-zoom saat fokus; tinggi sentuh ≥ 54px.
  - Tombol Daftar menempel di bawah layar (sticky) selama form terlihat, aman untuk notch (safe-area).
--}}
@push('styles')
<style>
  /* ---------------------------------------------------------------- Hero --- */
  .rtg-hero{position:relative;min-height:clamp(300px,52svh,440px);display:flex;align-items:flex-end;overflow:hidden;
    background:#000 var(--hero-sm) center 30%/cover no-repeat;color:#fff}
  .rtg-hero::before{content:'';position:absolute;inset:0;
    background:linear-gradient(180deg,rgba(0,0,0,.25) 0%,rgba(0,0,0,.1) 35%,rgba(0,0,0,.72) 78%,var(--bg) 100%)}
  .rtg-hero-in{position:relative;width:100%;padding:0 16px 44px}
  .rtg-eye{font-family:'Archivo',sans-serif;font-size:11px;letter-spacing:.22em;text-transform:uppercase;font-weight:700;
    display:inline-flex;align-items:center;gap:10px}
  .rtg-eye::before{content:'';width:22px;height:2px;background:var(--red)}
  .rtg-title{font-family:'Archivo',sans-serif;font-weight:900;font-size:clamp(46px,14vw,96px);line-height:.88;letter-spacing:-.02em;
    text-transform:uppercase;margin-top:10px;text-shadow:0 6px 28px rgba(0,0,0,.45)}
  .rtg-title span{color:var(--red)}
  .rtg-chips{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px}
  .rtg-chip{display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border-radius:999px;background:rgba(0,0,0,.45);
    border:1px solid rgba(255,255,255,.22);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);
    font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;white-space:nowrap}
  .rtg-chip svg{width:14px;height:14px;color:var(--red);flex-shrink:0}
  .rtg-chip.live i{width:7px;height:7px;border-radius:50%;background:#35E656;box-shadow:0 0 0 0 rgba(53,230,86,.6);animation:rtgPing 1.8s infinite}
  .rtg-chip.tree svg{color:#35E656}
  .rtg-chip.tree{border-color:rgba(53,230,86,.4)}

  /* Agenda: Fun Trail + Tanam Pohon */
  .rtg-agenda{display:grid;gap:10px;margin-top:20px}
  .rtg-ag{display:flex;gap:14px;align-items:center;padding:14px 16px;border-radius:18px;background:var(--bg-soft);border:1px solid var(--line)}
  .rtg-ag .ic{flex:none;width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:var(--red-soft);color:var(--red)}
  .rtg-ag.tree .ic{background:rgba(53,230,86,.12);color:#35E656}
  .rtg-ag .ic svg{width:24px;height:24px}
  .rtg-ag b{display:block;font-family:'Archivo',sans-serif;font-weight:900;font-size:16px;text-transform:uppercase;letter-spacing:-.005em}
  .rtg-ag span{display:block;margin-top:3px;color:var(--text-mute);font-size:13.5px;line-height:1.45}
  @keyframes rtgPing{70%{box-shadow:0 0 0 7px rgba(53,230,86,0)}100%{box-shadow:0 0 0 0 rgba(53,230,86,0)}}

  /* ------------------------------------------------- Sheet (form / status) --- */
  .rtg-sheet{position:relative;margin-top:-22px;padding:0 0 40px;background:var(--bg);border-radius:24px 24px 0 0}
  .rtg-body{max-width:560px;margin:0 auto;padding:26px 16px 0}
  .rtg-lead{color:var(--text-soft);font-size:15px;line-height:1.6}
  .rtg-h{font-family:'Archivo',sans-serif;font-weight:900;font-size:22px;text-transform:uppercase;letter-spacing:-.01em;margin-top:26px}
  .rtg-h small{display:block;margin-top:6px;font-family:inherit;font-weight:400;font-size:13.5px;letter-spacing:0;text-transform:none;color:var(--text-mute);line-height:1.5}

  .rtg-form{display:flex;flex-direction:column;gap:18px;margin-top:20px}
  .rtg-fld{display:flex;flex-direction:column;gap:8px}
  .rtg-fld > label{font-family:'Archivo',sans-serif;font-weight:700;font-size:12px;letter-spacing:.12em;text-transform:uppercase;color:var(--text-soft)}
  .rtg-fld .req{color:var(--red)}
  .rtg-fld .opt{color:var(--text-mute);letter-spacing:.02em;text-transform:none;font-weight:600}
  .rtg-in{position:relative}
  .rtg-in svg{position:absolute;left:16px;top:17px;width:20px;height:20px;color:var(--text-mute);pointer-events:none;transition:color .15s}
  .rtg-in input,.rtg-in textarea{display:block;width:100%;min-height:54px;padding:15px 16px 15px 48px;border-radius:16px;
    border:1.5px solid var(--line-strong);background:var(--bg-soft);color:#fff;font:inherit;font-size:16px;line-height:1.4;
    -webkit-appearance:none;appearance:none;transition:border-color .15s,background .15s}
  .rtg-in textarea{resize:vertical;min-height:96px}
  .rtg-in input:focus,.rtg-in textarea:focus{outline:none;border-color:var(--red);background:#1b1b1b}
  .rtg-in:focus-within svg{color:var(--red)}
  .rtg-in input::placeholder,.rtg-in textarea::placeholder{color:var(--text-mute)}
  .rtg-fld.bad .rtg-in input,.rtg-fld.bad .rtg-in textarea{border-color:var(--red)}
  .rtg-err{display:flex;gap:6px;align-items:center;color:#ff8a80;font-size:13px}
  .rtg-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}

  /* Tombol menempel di bawah layar selama form terlihat. */
  .rtg-submit{position:sticky;bottom:0;z-index:5;margin:4px -16px 0;padding:14px 16px calc(14px + env(safe-area-inset-bottom));
    background:linear-gradient(180deg,rgba(10,10,10,0),var(--bg) 34%)}
  .rtg-btn{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:56px;border:0;border-radius:999px;cursor:pointer;
    background:var(--red);color:#fff;font-family:'Archivo',sans-serif;font-weight:800;font-size:14px;letter-spacing:.1em;text-transform:uppercase;
    box-shadow:0 14px 30px -12px rgba(229,57,53,.7);transition:background .2s,transform .1s;-webkit-tap-highlight-color:transparent}
  .rtg-btn:active{transform:scale(.98)}
  .rtg-btn:hover{background:var(--red-deep)}
  .rtg-btn[disabled]{opacity:.7;cursor:progress}
  .rtg-btn.alt{background:transparent;border:1.5px solid var(--line-strong);box-shadow:none}
  .rtg-btn.wa{background:#1fa855;box-shadow:0 14px 30px -12px rgba(31,168,85,.7)}
  .rtg-btn svg{width:20px;height:20px}
  .rtg-note{margin-top:10px;text-align:center;font-size:12.5px;color:var(--text-mute);line-height:1.5}

  .rtg-state{margin-top:26px;padding:26px 20px;border-radius:22px;text-align:center;border:1px solid var(--line);background:var(--bg-soft)}
  .rtg-state .ic{width:72px;height:72px;margin:0 auto 16px;border-radius:50%;display:grid;place-items:center}
  .rtg-state .ic svg{width:36px;height:36px}
  .rtg-state.ok{border-color:rgba(53,230,86,.35);background:radial-gradient(320px 160px at 50% 0%,rgba(53,230,86,.14),transparent 70%),var(--bg-soft)}
  .rtg-state.ok .ic{background:rgba(53,230,86,.14);color:#35E656}
  .rtg-state.closed .ic{background:var(--red-soft);color:var(--red)}
  .rtg-state h2{font-family:'Archivo',sans-serif;font-weight:900;font-size:26px;text-transform:uppercase;line-height:1}
  .rtg-state p{margin-top:10px;color:var(--text-soft);font-size:15px;line-height:1.55}
  .rtg-actions{display:flex;flex-direction:column;gap:10px;margin-top:22px}

  /* ------------------------------------------------------- Layar lebar --- */
  .rtg-chip.full{background:var(--red);border-color:var(--red)}

  /* Sisa kuota */
  .rtg-quota{margin-top:18px;padding:14px 16px;border-radius:16px;background:var(--bg-soft);border:1px solid var(--line)}
  .rtg-quota-top{display:flex;justify-content:space-between;align-items:baseline;gap:10px;font-size:13px;color:var(--text-mute)}
  .rtg-quota-top span:first-child{font-family:'Archivo',sans-serif;font-weight:800;font-size:14px;text-transform:uppercase;letter-spacing:.06em;color:#fff}
  .rtg-quota-top b{color:#35E656;font-size:18px}
  .rtg-quota-bar{height:8px;margin-top:10px;border-radius:99px;background:rgba(255,255,255,.08);overflow:hidden}
  .rtg-quota-bar i{display:block;height:100%;min-width:4px;border-radius:inherit;background:linear-gradient(90deg,#35E656,#9be15d)}
  .rtg-quota.low{border-color:rgba(229,57,53,.4);background:var(--red-soft)}
  .rtg-quota.low b{color:var(--red)}
  .rtg-quota.low .rtg-quota-bar i{background:linear-gradient(90deg,#ff7043,var(--red))}

  @media (min-width:600px){ .rtg-agenda{grid-template-columns:1fr 1fr} }
  @media (min-width:900px){
    /* Foto resolusi penuh hanya di layar lebar; HP cukup webp ringan. */
    .rtg-hero{min-height:460px;background-image:var(--hero-lg);background-position:center 35%}
    .rtg-hero-in{max-width:1320px;margin:0 auto;padding:0 32px 96px;text-align:center}
    .rtg-chips{justify-content:center}
    .rtg-sheet{margin-top:-64px;background:transparent;padding-bottom:110px}
    .rtg-body{max-width:640px;padding:36px 36px 34px;border-radius:28px;background:var(--bg);border:1px solid var(--line);
      box-shadow:0 40px 80px -40px rgba(0,0,0,.9)}
    .rtg-submit{position:static;margin:8px 0 0;padding:0;background:none}
  }
  @media (prefers-reduced-motion:reduce){ .rtg-chip.live i{animation:none} }
</style>
@endpush

@section('content')
<header class="rtg-hero" style="--hero-sm:url('{{ asset('assets/gtr/gallery/rudi.webp') }}');--hero-lg:url('{{ asset('assets/gtr/gallery/rudi.jpeg') }}')">
  <div class="rtg-hero-in">
    <div class="rtg-eye">Road to GTR</div>
    <h1 class="rtg-title">Fun <span>Trail</span></h1>
    <div class="rtg-chips">
      <span class="rtg-chip">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        {{ $dateText }}
      </span>
      <span class="rtg-chip tree">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-7"/><path d="M12 15c-4 0-7-2.6-7-6.2C5 5.3 8.1 2 12 2s7 3.3 7 6.8c0 3.6-3 6.2-7 6.2Z"/><path d="m9 11 3 2.5 3-2.5"/></svg>
        Tanam Pohon
      </span>
      @if($remaining === 0)
        <span class="rtg-chip full">Kuota penuh</span>
      @elseif($total > 0)
        <span class="rtg-chip live"><i></i>{{ $total }}/{{ $quota }} terdaftar</span>
      @endif
    </div>
  </div>
</header>

<section class="rtg-sheet">
  <div class="rtg-body">
    <p class="rtg-lead">Pemanasan bareng sebelum Gerung Trail Run 2026 — lari santai di trail, lalu tanam pohon bareng. Kenalan sama trail-nya, latihan bareng komunitas, sekalian ikut merawat alam tempat kita berlari.</p>

    <div class="rtg-agenda">
      <div class="rtg-ag">
        <div class="ic"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 4l5.5 10.5-3.2-1.6L12 16l-2.3-3.1-3.2 1.6z"/></svg></div>
        <div><b>Fun Trail</b><span>Lari santai bareng, rasain vibe race day lebih awal.</span></div>
      </div>
      <div class="rtg-ag tree">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22v-7"/><path d="M12 15c-4 0-7-2.6-7-6.2C5 5.3 8.1 2 12 2s7 3.3 7 6.8c0 3.6-3 6.2-7 6.2Z"/><path d="m9 11 3 2.5 3-2.5"/></svg></div>
        <div><b>Tanam Pohon</b><span>Tanam pohon bareng peserta — kontribusi kecil buat alam.</span></div>
      </div>
    </div>

    @if(session('success'))
      <div class="rtg-state ok" role="status">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg></div>
        <h2>Kamu terdaftar!</h2>
        <p>{{ session('success') }}</p>
        @if($remaining > 0)
        <div class="rtg-actions">
          <a class="rtg-btn wa" href="https://wa.me/?text={{ rawurlencode($shareText) }}" target="_blank" rel="noopener">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.8 11.8 0 0 0 4.5 4c1.7.7 2.3.8 3.2.6a2.7 2.7 0 0 0 1.8-1.2c.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z"/></svg>
            Ajak teman via WhatsApp
          </a>
          <a class="rtg-btn alt" href="{{ route('gtr.road') }}#daftar">Daftarkan orang lain</a>
        </div>
        @endif
      </div>
    @elseif(! $open)
      <div class="rtg-state closed">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
        <h2>Pendaftaran ditutup</h2>
        <p>Road to GTR (fun trail &amp; tanam pohon) sudah berlangsung. Terima kasih buat semua yang sudah ikut — sampai ketemu di Gerung Trail Run 2026!</p>
        <div class="rtg-actions"><a class="rtg-btn" href="{{ route('gtr') }}">Ke Gerung Trail Run 2026</a></div>
      </div>
    @elseif($remaining === 0)
      <div class="rtg-state closed" id="daftar">
        <div class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="m17 9 4 4m0-4-4 4"/></svg></div>
        <h2>Kuota penuh</h2>
        <p>Kuota {{ $quota }} peserta Road to GTR sudah terpenuhi. Terima kasih atas antusiasmenya — sampai ketemu di Gerung Trail Run 2026!</p>
        <div class="rtg-actions"><a class="rtg-btn" href="{{ route('gtr') }}">Ke Gerung Trail Run 2026</a></div>
      </div>
    @else
      <h2 class="rtg-h" id="daftar">Form Pendaftaran<small>Panitia akan menghubungi kamu lewat WhatsApp untuk info tikum &amp; jam kumpul.</small></h2>

      {{-- Sisa kuota: bilah terisi = sudah terdaftar; merah saat ≤ 20 slot. --}}
      <div @class(['rtg-quota', 'low' => $remaining <= 20])>
        <div class="rtg-quota-top">
          <span>Sisa <b>{{ $remaining }}</b> slot</span>
          <span>{{ $total }} / {{ $quota }} terdaftar</span>
        </div>
        <div class="rtg-quota-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $quota }}" aria-valuenow="{{ $total }}" aria-label="Kuota terisi">
          <i style="width:{{ round($total / $quota * 100, 1) }}%"></i>
        </div>
      </div>

      <form class="rtg-form" id="rtg-form" method="POST" action="{{ route('gtr.road.store') }}" novalidate>
        @csrf
        <div class="rtg-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <div @class(['rtg-fld', 'bad' => $errors->has('name')])>
          <label for="name">Nama <span class="req">*</span></label>
          <div class="rtg-in">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Nama lengkap" maxlength="120"
              autocomplete="name" autocapitalize="words" enterkeyhint="next" required>
          </div>
          @error('name')<span class="rtg-err">{{ $message }}</span>@enderror
        </div>

        <div @class(['rtg-fld', 'bad' => $errors->has('phone')])>
          <label for="phone">No. HP / WhatsApp <span class="req">*</span></label>
          <div class="rtg-in">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/></svg>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" maxlength="20"
              inputmode="tel" autocomplete="tel" enterkeyhint="next" required>
          </div>
          @error('phone')<span class="rtg-err">{{ $message }}</span>@enderror
        </div>

        <div @class(['rtg-fld', 'bad' => $errors->has('community')])>
          <label for="community">Komunitas <span class="opt">— opsional</span></label>
          <div class="rtg-in">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/></svg>
            <input type="text" id="community" name="community" value="{{ old('community') }}" placeholder="Kosongkan kalau tidak ada" maxlength="120"
              autocapitalize="words" enterkeyhint="next">
          </div>
          @error('community')<span class="rtg-err">{{ $message }}</span>@enderror
        </div>

        <div @class(['rtg-fld', 'bad' => $errors->has('address')])>
          <label for="address">Alamat <span class="req">*</span></label>
          <div class="rtg-in">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <textarea id="address" name="address" rows="3" placeholder="Desa / kecamatan / kabupaten" maxlength="500" enterkeyhint="done" required>{{ old('address') }}</textarea>
          </div>
          @error('address')<span class="rtg-err">{{ $message }}</span>@enderror
        </div>

        <div class="rtg-submit">
          <button type="submit" class="rtg-btn" id="rtg-btn">Daftar Road to GTR <span aria-hidden="true">→</span></button>
          <p class="rtg-note">Data kamu hanya dipakai panitia untuk menghubungi kamu soal Road to GTR.</p>
        </div>
      </form>
    @endif
  </div>
</section>
@endsection

@push('scripts')
<script>
  (function () {
    var form = document.getElementById('rtg-form');
    if (!form) return;
    // Error dari server → gulir ke kolom pertama yang salah (keyboard HP tidak menutupinya).
    var bad = form.querySelector('.rtg-fld.bad input, .rtg-fld.bad textarea');
    if (bad) { bad.scrollIntoView({ block: 'center' }); bad.focus({ preventScroll: true }); }
    // Cegah kirim dobel saat sinyal lambat.
    form.addEventListener('submit', function () {
      var btn = document.getElementById('rtg-btn');
      btn.disabled = true; btn.textContent = 'Mengirim…';
    });
  })();
</script>
@endpush
