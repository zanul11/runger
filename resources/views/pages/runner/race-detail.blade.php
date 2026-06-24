@extends('layouts.runner-app')
@section('title', 'Detail Event — Runger')
@section('app-title', 'Detail Event')

@push('styles')
@verbatim
<style>
  .evd-back{display:inline-flex;align-items:center;gap:7px;color:var(--soft);font-size:13px;font-weight:600;margin-bottom:14px}
  .evd-banner{
    position:relative;overflow:hidden;border-radius:18px;padding:20px;color:#fff;margin-bottom:16px;
    background-size:cover;background-position:center;background-color:#1B3FAE;
    box-shadow:0 14px 34px rgba(0,0,0,.4);
  }
  .evd-banner::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,rgba(0,0,0,.35),rgba(0,0,0,.7))}
  .evd-banner > *{position:relative;z-index:1}
  .evd-banner img{height:30px;width:auto;border-radius:6px;margin-bottom:14px}
  .evd-dist{display:inline-block;background:#fff;color:#0F2680;font-family:'Poppins',sans-serif;font-weight:900;font-size:26px;padding:5px 16px;border-radius:10px;line-height:1}
  .evd-date{display:inline-block;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.3);padding:7px 14px;border-radius:10px;font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;margin-top:10px}

  .evd-card{background:#fff;color:#101010;border-radius:16px;padding:18px 20px;margin-bottom:16px}
  .evd-title{font-family:'Poppins',sans-serif;font-weight:800;font-size:18px;text-align:center}
  .evd-sub{text-align:center;color:#888;font-size:13px;margin-top:2px}
  .evd-h{font-family:'Poppins',sans-serif;font-weight:800;font-size:17px;margin:18px 0 12px}
  .evd-h:first-child{margin-top:6px}
  .evd-row{display:flex;font-size:14px;padding:5px 0;color:#333}
  .evd-row .k{width:108px;flex-shrink:0;color:#777}
  .evd-row .v{flex:1;font-weight:600;color:#101010;word-break:break-word}
  .evd-row .v .copy{background:none;border:none;cursor:pointer;color:#888;padding:0 0 0 6px;vertical-align:middle}
  .evd-status{display:inline-block;font-family:'Poppins',sans-serif;font-weight:700;font-size:10px;letter-spacing:.08em;text-transform:uppercase;padding:5px 11px;border-radius:999px;margin-left:8px}
  .evd-status.paid,.evd-status.confirmed{background:#DCFCE7;color:#15803D}
  .evd-status.pending{background:#FEF9C3;color:#A16207}
  .evd-status.cancelled{background:#F3F4F6;color:#6B7280}

  .qr-card{background:#fff;border-radius:16px;padding:26px;margin-bottom:16px;display:flex;flex-direction:column;align-items:center;gap:14px}
  .qr-card img{width:230px;height:230px;max-width:80%}
  .qr-card .qcap{font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;color:#101010;letter-spacing:.02em}

  .evd-actions{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:8px}
  .evd-act{display:inline-flex;align-items:center;justify-content:center;background:var(--blue);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;letter-spacing:.04em;text-transform:uppercase;padding:14px;border-radius:999px;border:none;cursor:not-allowed;opacity:.85}
  .pay-cta{background:#fff;border:1px solid var(--line);border-radius:16px;padding:24px 20px;text-align:center;box-shadow:0 6px 20px rgba(15,24,48,.05)}
  .pay-cta-h{font-family:'Poppins',sans-serif;font-weight:800;font-size:18px;color:var(--text)}
  .pay-cta p{font-size:13px;color:var(--soft);margin:8px auto 14px;max-width:34ch;line-height:1.5}
  .pay-cta-amt{font-family:'Poppins',sans-serif;font-weight:900;font-size:24px;color:var(--blue);margin-bottom:16px}
  .pay-cta-btn{width:100%;background:var(--blue);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:14px;letter-spacing:.04em;text-transform:uppercase;padding:15px;border-radius:12px;border:none;cursor:pointer;transition:background .2s,transform .15s}
  .pay-cta-btn:hover{background:var(--blue-deep);transform:translateY(-1px)}
</style>
@endverbatim
@endpush

@section('content')
@php
  $qrData = $reg->nomor_registrasi . ' | ' . ($reg->category->name ?? '') . ' | ' . $reg->full_name;
  $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=0&data=' . urlencode($qrData);
@endphp

<a class="evd-back" href="{{ route('gtr.account.race') }}">← My Race</a>

<div class="evd-banner" style="background-image:url('{{ $reg->category->header_url }}')">
  <img src="{{ asset('assets/gtr/logo.jpeg') }}" alt="Gerung Trail Run">
  <div><span class="evd-dist">{{ $reg->category->distance ?? '' }}</span></div>
  <div class="evd-date">Minggu, 29 November 2026</div>
</div>

<div class="evd-card">
  <div class="evd-title">Gerung Trail Run 2026</div>
  <div class="evd-sub">29 November 2026 · Bukit Keteri</div>

  <div class="evd-h">Race Info</div>
  <div class="evd-row"><span class="k">Order ID</span><span class="v">{{ $reg->nomor_registrasi }} <button type="button" class="copy" data-copy="{{ $reg->nomor_registrasi }}" aria-label="Salin">⧉</button><span class="evd-status {{ $reg->payment_status }}">{{ ucfirst($reg->payment_status) }}</span></span></div>
  <div class="evd-row"><span class="k">Distance</span><span class="v">{{ $reg->category->distance ?? '-' }}</span></div>
  <div class="evd-row"><span class="k">Category</span><span class="v">{{ $reg->category->name ?? '-' }}</span></div>

  <div class="evd-h">Participant</div>
  <div class="evd-row"><span class="k">BIB</span><span class="v">{{ $reg->bib_number ?: '-' }}</span></div>
  <div class="evd-row"><span class="k">Name</span><span class="v">{{ $reg->full_name }}</span></div>
  <div class="evd-row"><span class="k">Gender</span><span class="v">{{ $reg->gender }}</span></div>
  <div class="evd-row"><span class="k">Jersey</span><span class="v">{{ $reg->size }}</span></div>
  <div class="evd-row"><span class="k">Klub</span><span class="v">{{ $reg->club ?: '-' }}</span></div>
  <div class="evd-row"><span class="k">Gol. Darah</span><span class="v">{{ $reg->blood_type }}</span></div>
</div>

@if($reg->payment_status === 'paid')
  <div class="qr-card">
    <img src="{{ $qrUrl }}" alt="QR {{ $reg->nomor_registrasi }}" loading="lazy">
    <div class="qcap">{{ $reg->nomor_registrasi }}</div>
  </div>

  <div class="evd-actions">
    <button type="button" class="evd-act" disabled>Result</button>
    <button type="button" class="evd-act" disabled>My Photo</button>
  </div>
@else
  <div class="pay-cta">
    <div class="pay-cta-h">Selesaikan Pembayaran</div>
    <p>E-ticket (QR code) akan muncul di sini setelah pembayaran kamu lunas.</p>
    <div class="pay-cta-amt">{{ $reg->amount ? 'IDR ' . number_format($reg->amount, 0, ',', '.') : ($reg->category->early_bird_formatted ?? '') }}</div>
    <form method="POST" action="{{ route('gtr.payment.pay', $reg) }}">
      @csrf
      <button type="submit" class="pay-cta-btn">{{ $reg->payment_status === 'cancelled' ? 'Bayar Lagi' : 'Bayar Sekarang' }}</button>
    </form>
  </div>
@endif

@push('scripts')
@verbatim
<script>
  document.querySelectorAll('.copy').forEach(b => b.addEventListener('click', () => {
    navigator.clipboard?.writeText(b.dataset.copy);
    const o = b.textContent; b.textContent = '✓'; setTimeout(() => b.textContent = o, 1200);
  }));
</script>
@endverbatim
@endpush
@endsection
