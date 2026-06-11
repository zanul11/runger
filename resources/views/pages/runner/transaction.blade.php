@extends('layouts.runner-app')
@section('title', 'Transaction — Akun Peserta GTR')
@section('app-title', 'Transaction')

@push('styles')
@verbatim
<style>
  .trx{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
  .trx .nm{font-family:'Archivo',sans-serif;font-weight:800;font-size:16px}
  .trx .mt{font-size:12px;color:var(--mute);margin-top:4px}
  .trx .amt{font-family:'Archivo',sans-serif;font-weight:800;font-size:15px;text-align:right}
  .trx-foot{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-top:14px;padding-top:14px;border-top:1px solid var(--line)}
</style>
@endverbatim
@endpush

@section('content')
<div class="sec-title">Transaction</div>
<div class="sec-sub">Riwayat & pembayaran pendaftaranmu.</div>

@forelse($registrations as $reg)
  <div class="card">
    <div class="trx">
      <div>
        <div class="nm">{{ $reg->category->distance ?? '' }} · {{ $reg->category->name ?? '-' }}</div>
        <div class="mt">{{ optional($reg->registered_at)->translatedFormat('d M Y · H:i') }}</div>
      </div>
      <div>
        <div class="amt">{{ $reg->category->early_bird_formatted ?? '-' }}</div>
        <span class="badge {{ $reg->payment_status }}" style="float:right;margin-top:6px">{{ ucfirst($reg->payment_status) }}</span>
      </div>
    </div>
    @if($reg->payment_status === 'pending')
      <div class="trx-foot">
        <span style="font-size:12.5px;color:var(--mute)">Menunggu pembayaran</span>
        <form method="POST" action="{{ route('gtr.payment.pay', $reg) }}">
          @csrf
          <button type="submit" class="btn-blue">Bayar Sekarang</button>
        </form>
      </div>
    @elseif($reg->payment_status === 'paid')
      <div class="trx-foot">
        <span style="font-size:12.5px;color:#15803D;font-weight:600">✓ Lunas{{ $reg->paid_at ? ' · ' . $reg->paid_at->translatedFormat('d M Y') : '' }}</span>
      </div>
    @endif
  </div>
@empty
  <div class="card"><div class="empty">Belum ada transaksi. Daftar lomba dulu di tab Home.</div></div>
@endforelse
@endsection
