@extends('layouts.runner-app')
@section('title', 'Status Pembayaran — Runger')
@section('app-title', 'Status Pembayaran')

@push('styles')
@verbatim
<style>
  .pf{display:flex;flex-direction:column;align-items:center;text-align:center;padding:18px 8px 6px}
  .pf-ic{width:88px;height:88px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:18px}
  .pf-ic svg{width:44px;height:44px;stroke-width:2.5}
  .pf.paid .pf-ic{background:#DCFCE7;color:#15803D}
  .pf.pending .pf-ic{background:#FEF9C3;color:#A16207}
  .pf.cancelled .pf-ic{background:#FEE2E2;color:#B42318}
  .pf-title{font-family:'Poppins',sans-serif;font-weight:900;font-size:26px;line-height:1.05}
  .pf-msg{color:var(--soft);font-size:14px;margin-top:8px;max-width:34ch}
  .pf-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:18px 20px;margin-top:22px;box-shadow:0 6px 20px rgba(15,24,48,.05)}
  .pf-row{display:flex;justify-content:space-between;gap:14px;padding:9px 0;border-bottom:1px solid var(--line);font-size:14px}
  .pf-row:last-child{border-bottom:none}
  .pf-row .k{color:var(--mute)}
  .pf-row .v{font-weight:700;text-align:right;color:var(--text)}
  .pf-actions{display:flex;flex-direction:column;gap:10px;margin-top:20px}
  .pf-btn{display:inline-flex;align-items:center;justify-content:center;padding:14px;border-radius:12px;font-family:'Poppins',sans-serif;font-weight:700;font-size:13px;letter-spacing:.04em;text-transform:uppercase}
  .pf-btn.primary{background:var(--blue);color:#fff}
  .pf-btn.ghost{background:#fff;border:1px solid var(--line);color:var(--blue)}
</style>
@endverbatim
@endpush

@section('content')
@php
  $status = $reg->payment_status ?? 'pending';
  $map = [
    'paid' => ['Pembayaran Berhasil', 'Pendaftaranmu sudah lunas. Sampai jumpa di garis start! 🏃'],
    'pending' => ['Menunggu Pembayaran', 'Pembayaran kamu belum selesai. Selesaikan agar slot tetap aman.'],
    'cancelled' => ['Pembayaran Gagal', 'Transaksi dibatalkan atau kedaluwarsa. Silakan coba bayar lagi.'],
  ];
  [$title, $msg] = $map[$status] ?? $map['pending'];
@endphp

<div class="pf {{ $status }}">
  <div class="pf-ic">
    @if($status === 'paid')
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
    @elseif($status === 'cancelled')
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
    @else
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
    @endif
  </div>
  <div class="pf-title">{{ $title }}</div>
  <p class="pf-msg">{{ $msg }}</p>
</div>

@if($reg)
<div class="pf-card">
  <div class="pf-row"><span class="k">No. Registrasi</span><span class="v">{{ $reg->nomor_registrasi }}</span></div>
  <div class="pf-row"><span class="k">Kategori</span><span class="v">{{ $reg->category->distance ?? '' }} · {{ $reg->category->name ?? '' }}</span></div>
  @php
    $base = $reg->baseAmount();
    $fee = \App\Models\GtrRegistration::ADMIN_FEE;
  @endphp
  <div class="pf-row"><span class="k">Biaya Pendaftaran</span><span class="v">IDR {{ number_format($base, 0, ',', '.') }}</span></div>
  <div class="pf-row"><span class="k">Biaya Layanan <span class="info-i">?<span class="bubble">Biaya untuk pengelolaan sistem pendaftaran, e-ticket, dan dukungan peserta.</span></span></span><span class="v">IDR {{ number_format($fee, 0, ',', '.') }}</span></div>
  <div class="pf-row"><span class="k">Total</span><span class="v"><strong>IDR {{ number_format($base + $fee, 0, ',', '.') }}</strong></span></div>
  <div class="pf-row"><span class="k">Metode</span><span class="v">{{ $reg->pay }}</span></div>
  <div class="pf-row"><span class="k">Status</span><span class="v"><span class="badge {{ $reg->payment_status }}">{{ ucfirst($reg->payment_status) }}</span></span></div>
  @if($reg->paid_at)
    <div class="pf-row"><span class="k">Dibayar</span><span class="v">{{ $reg->paid_at->translatedFormat('d M Y · H:i') }}</span></div>
  @endif
</div>
@endif

<div class="pf-actions">
  @if($reg && $reg->payment_status === 'pending')
    <form method="POST" action="{{ route('gtr.payment.pay', $reg) }}">
      @csrf
      <button type="submit" class="pf-btn primary" style="width:100%;border:none;cursor:pointer">Bayar Lagi</button>
    </form>
  @endif
  <a class="pf-btn primary" href="{{ route('gtr.account.race') }}">Lihat My Race</a>
  <a class="pf-btn ghost" href="{{ route('gtr.account.transaction') }}">Kembali ke Transaction</a>
</div>
@endsection
