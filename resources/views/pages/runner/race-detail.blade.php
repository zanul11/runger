@extends('layouts.runner-app')
@section('title', 'Race — Runger')

@php
  $cat = $reg->category;
  $paid = $reg->payment_status === 'paid';
  $isPackMode = ($dayCard['mode'] ?? null) === \App\Support\RaceDayCard::RACEPACK;
  // QR di lembar "My QR" layout = registrasi lunas pertama; registrasi lain ditampilkan inline.
  $sheetRegId = $allRegs->where('payment_status', 'paid')->whereNotNull('nomor_registrasi')->first()?->id;
  $guideReady = $setting?->raceGuidePublished();
  $rpcReq = \App\Models\GtrSetting::lines($setting?->rpc_requirements);
@endphp

@push('styles')
@verbatim
<style>
  /* pilih registrasi (bila > 1) */
  .rn-switch{display:flex;gap:6px;overflow-x:auto;margin:0 -2px 14px;padding:2px;scrollbar-width:none}
  .rn-switch::-webkit-scrollbar{display:none}
  .rn-switch a{flex:0 0 auto;padding:8px 14px;border-radius:999px;background:#fff;border:1px solid var(--line);font-weight:800;font-size:12px;color:var(--soft)}
  .rn-switch a.on{background:#101010;border-color:#101010;color:#fff}

  /* kepala Runner Information */
  .rn-head{padding:2px 2px 4px}
  .rn-bib{font-weight:900;font-size:38px;line-height:1;letter-spacing:-.01em;color:var(--blue);font-variant-numeric:tabular-nums}
  .rn-bib.none{font-size:24px;color:var(--mute)}
  .rn-sub{font-size:13px;color:var(--soft);margin-top:6px;font-weight:600}
  .rn-sub b{color:var(--text)}

  /* kotak info sederhana (baris ikon + label + nilai) */
  .rn-box{background:#fff;border:1px solid var(--line);border-radius:18px;padding:6px 16px;box-shadow:0 6px 18px rgba(15,24,48,.05)}
  .rn-row{display:flex;gap:12px;align-items:flex-start;padding:11px 0;border-bottom:1px solid #F0F2F7}
  .rn-row:last-child{border-bottom:none}
  .rn-row svg{width:18px;height:18px;flex-shrink:0;color:var(--mute);margin-top:1px}
  .rn-row .k{width:84px;flex-shrink:0;font-size:13px;color:var(--soft)}
  .rn-row .v{flex:1;min-width:0;text-align:right;font-weight:700;font-size:13.5px;color:var(--text);line-height:1.4;word-break:break-word}
  .rn-row .v small{display:block;font-weight:500;font-size:12px;color:var(--soft);margin-top:2px}
  .rn-list{list-style:none;padding:12px 0 6px;font-size:12.5px;color:var(--soft);line-height:1.5}
  .rn-list li{position:relative;padding-left:14px;margin-bottom:4px}
  .rn-list li::before{content:'';position:absolute;left:0;top:8px;width:5px;height:5px;border-radius:50%;background:var(--red)}
  .rn-note{font-size:12.5px;color:var(--soft);line-height:1.5;padding:10px 0 8px}
  .rn-acts{display:flex;flex-wrap:wrap;gap:8px;padding:6px 0 12px}
  .rn-btn{flex:1 1 auto;display:inline-flex;align-items:center;justify-content:center;gap:7px;padding:12px 14px;border-radius:12px;border:none;cursor:pointer;
    font-family:'Poppins',sans-serif;font-weight:800;font-size:12px;letter-spacing:.03em;text-transform:uppercase;background:#101010;color:#fff;white-space:nowrap}
  .rn-btn.ghost{background:#F1F3F8;color:var(--text)}
  .rn-btn.red{background:var(--red);box-shadow:0 8px 18px rgba(229,57,53,.3)}
  .rn-btn[disabled]{opacity:.5;cursor:not-allowed}
  .rn-btn svg{width:16px;height:16px}
  .rn-status{display:flex;align-items:center;gap:10px;margin:2px 0 10px;padding:11px 13px;border-radius:13px;font-size:13px;font-weight:700}
  .rn-status.ok{background:#F0FDF4;color:#15803D}
  .rn-status.wait{background:#FFFBEB;color:#B45309}
  .rn-qr{display:flex;flex-direction:column;align-items:center;gap:8px;padding:14px 0}
  .rn-qr img{width:210px;max-width:70%;height:auto;aspect-ratio:1}
  .rn-qr b{font-size:13px}

  /* data peserta (lipat) */
  .rn-data{background:#fff;border:1px solid var(--line);border-radius:18px;padding:0 16px}
  .rn-data summary{list-style:none;cursor:pointer;display:flex;justify-content:space-between;align-items:center;padding:15px 0;font-weight:800;font-size:13.5px}
  .rn-data summary::-webkit-details-marker{display:none}
  .rn-data summary::after{content:'+';font-size:18px;color:var(--mute)}
  .rn-data[open] summary::after{content:'−'}
  .rn-data .rn-row{padding:9px 0}
  .rn-data .copy{background:none;border:none;cursor:pointer;color:var(--mute);padding:0 0 0 6px}

  /* live tracking */
  .trk-share-card{display:flex;flex-direction:column;gap:12px;background:#101010;color:#fff;border-radius:18px;padding:16px 18px}
  .trk-share-card .tk{font-weight:800;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#FF8A80}
  .trk-share-card .tt{font-size:13px;line-height:1.45;margin-top:3px;opacity:.9}
  .trk-share-card .tb{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .trk-share-card .tb a,.trk-share-card .tb button{display:inline-flex;align-items:center;justify-content:center;padding:11px;border-radius:12px;font-family:'Poppins',sans-serif;font-weight:800;font-size:12px;letter-spacing:.03em;text-transform:uppercase;border:none;cursor:pointer}
  .tb-open{background:rgba(255,255,255,.1);color:#fff}
  .tb-share{background:#E53935;color:#fff}
  .rn-two{display:grid;grid-template-columns:1fr 1fr;gap:10px}

  /* pembayaran */
  .pay-cta{background:#fff;border:1px solid var(--line);border-radius:18px;padding:22px 20px;text-align:center;box-shadow:0 6px 20px rgba(15,24,48,.05);margin-top:14px}
  .pay-cta-h{font-weight:800;font-size:18px;color:var(--text)}
  .pay-cta p{font-size:13px;color:var(--soft);margin:8px auto 14px;max-width:34ch;line-height:1.5}
  .pay-cta-amt{font-weight:900;font-size:24px;color:var(--blue);margin-bottom:16px}
  .pay-cta-btn{width:100%;background:var(--blue);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;letter-spacing:.04em;text-transform:uppercase;padding:15px;border-radius:12px;border:none;cursor:pointer}
</style>
@endverbatim
@endpush

@section('content')
@if($allRegs->count() > 1)
  <nav class="rn-switch" aria-label="Pilih race">
    @foreach($allRegs as $r)
      <a href="{{ route('gtr.account.race.detail', $r) }}" @class(['on' => $r->id === $reg->id])>{{ $r->category->distance ?? 'Race' }}</a>
    @endforeach
  </nav>
@endif

<div class="rn-head">
  @if($paid && $reg->bib_number)
    <div class="rn-bib">BIB {{ $reg->bib_number }}</div>
  @else
    <div class="rn-bib none">{{ $paid ? 'BIB segera terbit' : 'Belum lunas' }}</div>
  @endif
  <div class="rn-sub">
    <b>{{ $cat->distance ?? '' }}</b> · {{ $cat->name ?? '-' }}@if($cat?->start_time) · Start <b>{{ $cat->start_time }}</b>@endif
  </div>
  <div class="rn-sub">{{ $reg->full_name }} · {{ $setting?->date_text ?: 'Minggu, 29 November 2026' }}</div>
  <div class="rn-sub">📍 {{ $setting?->location_text ?: 'Bukit Keteri, Gerung — Lombok Barat' }}</div>
</div>

@if(! $paid)
  @php
    $base = $reg->baseAmount();
    $fee = $reg->serviceFee();
    $total = $base + $fee;
  @endphp
  <div class="pay-cta">
    <div class="pay-cta-h">Selesaikan Pembayaran</div>
    <p>E-ticket (QR code) &amp; BIB terbit setelah pembayaran kamu lunas.</p>
    <div class="pay-cta-amt">IDR {{ number_format($total, 0, ',', '.') }}@if($fee > 0) <span class="info-i">?<span class="bubble">Sudah termasuk biaya layanan IDR {{ number_format($fee, 0, ',', '.') }} — biaya untuk pengelolaan sistem pendaftaran, e-ticket, dan dukungan peserta.</span></span>@endif</div>
    @if(\App\Models\GtrSetting::registrationClosed())
      <div class="pay-cta-btn" style="opacity:.6;cursor:default">Pendaftaran &amp; pembayaran sudah ditutup</div>
    @else
      <form method="POST" action="{{ route('gtr.payment.pay', $reg) }}">
        @csrf
        <button type="submit" class="pay-cta-btn">{{ $reg->payment_status === 'cancelled' ? 'Bayar Lagi' : 'Bayar Sekarang' }}</button>
      </form>
    @endif
  </div>
@endif

@if($dayCard)
  <div style="margin-top:14px">
    @include('pages.runner.partials.race-day-card', ['card' => $dayCard, 'compact' => false])
  </div>
@endif

@if($cat)
  <div class="ttl">
    <h2>Race Guide</h2>
    @if($guideReady)<a href="{{ route('gtr.account.guide.view') }}">↗ Race guide</a>@endif
  </div>
  @include('pages.runner.partials.course-tabs', ['cat' => $cat, 'registration' => $reg, 'tabs' => ['map', 'gear', 'gpx']])
@endif

@if($paid)
  {{-- ===== Race Pack Collection ===== --}}
  <div class="ttl" id="racepack"><h2>Race Pack Collection</h2></div>
  <div class="rn-box">
    <div class="rn-status {{ $reg->racepack_collected_at ? 'ok' : 'wait' }}" style="margin-top:10px">
      @if($reg->racepack_collected_at)
        ✓ Sudah diambil · {{ $reg->racepack_collected_at->translatedFormat('d M Y, H:i') }} WITA{{ $reg->racepack_picked_by ? ' · diwakilkan ' . $reg->racepack_picked_by : '' }}
      @else
        Race pack belum diambil
      @endif
    </div>
    @if($setting?->racepack_schedule)
      <div class="rn-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><span class="k">Jadwal</span><span class="v">{{ $setting->racepack_schedule }}</span></div>
    @endif
    @if($setting?->racepack_location)
      <div class="rn-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><span class="k">Lokasi</span><span class="v">{{ $setting->racepack_location }}@if($setting->racepack_address)<small>{{ $setting->racepack_address }}</small>@endif</span></div>
    @endif
    @if($setting?->rpc_contact)
      <div class="rn-row"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span class="k">Kontak</span><span class="v">{{ $setting->rpc_contact }}</span></div>
    @endif
    @if($rpcReq)
      <ul class="rn-list">@foreach($rpcReq as $line)<li>{{ $line }}</li>@endforeach</ul>
    @elseif($setting?->racepack_note)
      <div class="rn-note">{{ $setting->racepack_note }}</div>
    @endif
    @if($setting?->rpc_proxy_note && ! $reg->racepack_collected_at)
      <div class="rn-note"><b>Diwakilkan?</b> {{ $setting->rpc_proxy_note }}</div>
    @endif

    @if(! $reg->racepack_collected_at && ! $isPackMode && $reg->id !== $sheetRegId)
      <div class="rn-qr"><img src="{{ $reg->qrDataUri() }}" alt="QR e-ticket {{ $reg->nomor_registrasi }}" width="210" height="210"><b>{{ $reg->nomor_registrasi }}</b></div>
    @endif
    <div class="rn-acts">
      @if(! $reg->racepack_collected_at && ! $isPackMode && $reg->id === $sheetRegId)
        <button type="button" class="rn-btn red" data-open-qr>Tampilkan QR e-ticket</button>
      @endif
      @if($setting?->racepack_maps_url)
        <a class="rn-btn ghost" href="{{ $setting->racepack_maps_url }}" target="_blank" rel="noopener">📍 Buka Maps</a>
      @endif
    </div>
  </div>

  {{-- ===== Race day & setelahnya ===== --}}
  @if($reg->bib_number && config('services.gtr.app_live_tracking'))
    <div class="ttl"><h2>Live Tracking</h2></div>
    <div class="trk-share-card">
      <div class="tt">Bagikan link agar keluarga bisa memantau posisimu saat race day.</div>
      <div class="tb">
        <a href="{{ route('gtr.track', $reg->bib_number) }}" class="tb-open">Buka</a>
        <button type="button" class="tb-share" data-share-url="{{ route('gtr.track', $reg->bib_number) }}" data-share-title="Lacak {{ $reg->full_name }} di Gerung Trail Run 2026">Bagikan</button>
      </div>
    </div>
  @endif

  @php
    $resultReady = $setting?->results_published && $reg->bib_number
      && $reg->result && in_array($reg->result->status, ['finisher', 'dnf', 'dq'], true);
    $photoCount = $reg->bib_number ? $reg->photos()->count() : 0;
  @endphp
  <div class="ttl"><h2>Hasil &amp; Foto</h2></div>
  <div class="rn-two">
    @if($resultReady)
      <a class="rn-btn" href="{{ route('gtr.result', $reg->bib_number) }}">Result</a>
    @else
      <button type="button" class="rn-btn" disabled title="Tersedia setelah hasil resmi diumumkan">Result</button>
    @endif
    @if($photoCount)
      <a class="rn-btn" href="{{ route('gtr.account.race.photos', $reg) }}">My Photo ({{ $photoCount }})</a>
    @else
      <button type="button" class="rn-btn" disabled title="Foto muncul setelah panitia menandai BIB-mu">My Photo</button>
    @endif
  </div>
@endif

{{-- ===== Data peserta ===== --}}
<div class="ttl"><h2>Data Peserta</h2></div>
<details class="rn-data">
  <summary>{{ $reg->full_name }} · {{ $reg->nomor_registrasi }}</summary>
  <div class="rn-row"><span class="k">No. Registrasi</span><span class="v">{{ $reg->nomor_registrasi }} <button type="button" class="copy" data-copy="{{ $reg->nomor_registrasi }}" aria-label="Salin">⧉</button></span></div>
  <div class="rn-row"><span class="k">Status</span><span class="v"><span class="badge {{ $reg->payment_status }}">{{ ucfirst($reg->payment_status) }}</span></span></div>
  <div class="rn-row"><span class="k">Kategori</span><span class="v">{{ $cat->distance ?? '-' }} · {{ $cat->name ?? '-' }}</span></div>
  <div class="rn-row"><span class="k">Gender</span><span class="v">{{ $reg->gender ?: '-' }}</span></div>
  <div class="rn-row"><span class="k">Jersey</span><span class="v">{{ $reg->size ?: '-' }}</span></div>
  <div class="rn-row"><span class="k">Klub</span><span class="v">{{ $reg->club ?: '-' }}</span></div>
  <div class="rn-row"><span class="k">Gol. Darah</span><span class="v">{{ $reg->blood_type ?: '-' }}</span></div>
  <div class="rn-row"><span class="k">Kontak Darurat</span><span class="v">{{ trim(($reg->emergency_name ?? '') . ' ' . ($reg->emergency_contact ?? '')) ?: '-' }}</span></div>
</details>

<div style="margin-top:16px">
  @include('pages.partials.gtr-weather', ['style' => 'card', 'theme' => 'light'])
</div>

@push('scripts')
@verbatim
<script>
  document.querySelectorAll('[data-share-url]').forEach(b => b.addEventListener('click', () => {
    const url = b.dataset.shareUrl, title = b.dataset.shareTitle;
    if (navigator.share) { navigator.share({ title, url }).catch(() => {}); return; }
    navigator.clipboard?.writeText(url);
    b.textContent = '✓ Disalin';
  }));
  document.querySelectorAll('.copy').forEach(b => b.addEventListener('click', () => {
    navigator.clipboard?.writeText(b.dataset.copy);
    const o = b.textContent; b.textContent = '✓'; setTimeout(() => b.textContent = o, 1200);
  }));
</script>
@endverbatim
@endpush
@endsection
