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
  .rp-card{background:#fff;border-radius:16px;padding:16px 18px;margin-bottom:16px;border:1px solid var(--line)}
  .rp-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:12px}
  .rp-k{font-weight:800;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:var(--red)}
  .rp-t{font-weight:900;font-size:26px;color:var(--text);line-height:1.1}
  .rp-t small{font-size:11px;font-weight:600;color:var(--mute)}
  .rp-band{padding:10px 14px;border-radius:999px;background:#101010;color:#fff;font-weight:800;font-size:11.5px;letter-spacing:.03em;text-transform:uppercase}
  .rp-list{display:flex;flex-direction:column;gap:6px}
  .rp-row{display:grid;grid-template-columns:60px 1fr auto 92px;align-items:center;gap:8px;padding:8px 10px;border-radius:10px;background:#F7F8FB}
  .rp-code{text-align:center;padding:3px 0;border-radius:6px;color:#fff;font-weight:900;font-size:10px;letter-spacing:.04em}
  .rp-km{font-size:12px;color:var(--soft);font-weight:600}
  .rp-eta{font-weight:900;font-size:15px;color:var(--text)}
  .rp-cot{text-align:center;font-size:10px;font-weight:800;padding:3px 6px;border-radius:999px;background:#EEF0F4;color:#555}
  .rp-cot.ok{background:#DCFCE7;color:#15803D}
  .rp-cot.warn{background:#FEF3C7;color:#B45309}
  .rp-cot.bad{background:#FEE2E2;color:#B91C1C}
  .trk-share-card{display:flex;flex-direction:column;gap:12px;background:#101010;color:#fff;border-radius:16px;padding:16px 18px;margin-bottom:16px}
  .trk-share-card .tk{font-weight:800;font-size:10px;letter-spacing:.14em;text-transform:uppercase;color:#FF8A80}
  .trk-share-card .tt{font-size:13px;line-height:1.45;margin-top:3px;opacity:.9}
  .trk-share-card .tb{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .trk-share-card .tb a,.trk-share-card .tb button{display:inline-flex;align-items:center;justify-content:center;padding:11px;border-radius:12px;font-family:'Poppins',sans-serif;font-weight:800;font-size:12px;letter-spacing:.03em;text-transform:uppercase;border:none;cursor:pointer}
  .tb-open{background:rgba(255,255,255,.1);color:#fff}
  .tb-share{background:#E53935;color:#fff}
  .rpk{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-radius:16px;padding:14px 16px;margin-bottom:16px}
  .rpk.done{background:#F0FDF4;border-color:#BBF7D0}
  .rpk-ic{width:34px;height:34px;flex-shrink:0;border-radius:10px;display:grid;place-items:center;background:#FEF3C7;font-size:17px}
  .rpk.done .rpk-ic{background:#22C55E;color:#fff;font-weight:900}
  .rpk-t{font-weight:800;font-size:14.5px;color:var(--text)}
  .rpk-s{font-size:12.5px;color:var(--soft);margin-top:3px;line-height:1.45}
  .rp-edit{display:block;text-align:center;margin-top:12px;font-weight:700;font-size:12.5px;color:var(--blue)}
</style>
@endverbatim
@endpush

@section('content')
@php
  $qrUrl = $reg->qrImageUrl(300);
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

@if(!empty($plan))
  @php
    $hm = fn ($m) => intdiv((int) $m, 60) . ':' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT);
    $clock = fn ($m) => $m === null ? '-' : str_pad((string) (intdiv((int) $m, 60) % 24), 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) ((int) $m % 60), 2, '0', STR_PAD_LEFT);
    $pc = ['start' => '#22C55E', 'finish' => '#101010', 'ws' => '#2EA6E6', 'cp' => '#F97316', 'cop' => '#DC2626'];
  @endphp
  <div class="rp-card">
    <div class="rp-head">
      <div>
        <div class="rp-k">Race Plan</div>
        <div class="rp-t">{{ $hm($planTarget) }} <small>{{ $reg->target_finish_minutes ? 'target kamu' : 'estimasi bawaan' }}</small></div>
      </div>
      <a class="rp-band" href="{{ route('gtr.account.race.paceband', $reg) }}">Pace Band</a>
    </div>
    <div class="rp-list">
      @foreach($plan as $row)
        <div class="rp-row">
          <span class="rp-code" style="background:{{ $pc[$row['type']] ?? '#101010' }}">{{ $row['code'] }}</span>
          <span class="rp-km">KM {{ rtrim(rtrim(number_format($row['km'], 1), '0'), '.') }}</span>
          <span class="rp-eta">{{ $clock($row['eta']) }}</span>
          @if($row['cutoff'] !== null)
            @php $buf = $row['eta'] !== null ? $row['cutoff'] - $row['eta'] : null; @endphp
            <span class="rp-cot {{ $buf === null ? '' : ($buf >= 15 ? 'ok' : ($buf >= 0 ? 'warn' : 'bad')) }}">COT {{ $clock($row['cutoff']) }}</span>
          @else
            <span></span>
          @endif
        </div>
      @endforeach
    </div>
    <a class="rp-edit" href="{{ route('gtr.account.category', $reg->category) }}#race-plan">{{ $reg->target_finish_minutes ? 'Ubah target' : 'Atur target finish' }} di Estimasi →</a>
  </div>
@endif

@if($reg->payment_status === 'paid')
  @php $setting = \App\Models\GtrSetting::first(); @endphp
  <div class="rpk {{ $reg->racepack_collected_at ? 'done' : '' }}">
    <span class="rpk-ic">{{ $reg->racepack_collected_at ? '✓' : '🎁' }}</span>
    <div>
      <div class="rpk-t">{{ $reg->racepack_collected_at ? 'Race pack sudah diambil' : 'Race pack belum diambil' }}</div>
      @if($reg->racepack_collected_at)
        <div class="rpk-s">{{ $reg->racepack_collected_at->translatedFormat('d M Y, H:i') }} WITA{{ $reg->racepack_picked_by ? ' · diwakilkan ' . $reg->racepack_picked_by : '' }}</div>
      @else
        @if($setting?->racepack_schedule)<div class="rpk-s">🗓 {{ $setting->racepack_schedule }}</div>@endif
        @if($setting?->racepack_location)<div class="rpk-s">📍 {{ $setting->racepack_location }}</div>@endif
        <div class="rpk-s">Tunjukkan QR di bawah ini ke petugas.{{ $setting?->racepack_note ? ' ' . $setting->racepack_note : '' }}</div>
      @endif
    </div>
  </div>

  <div class="qr-card">
    <img src="{{ $qrUrl }}" alt="QR {{ $reg->nomor_registrasi }}" loading="lazy">
    <div class="qcap">{{ $reg->nomor_registrasi }}</div>
  </div>

  @if($reg->bib_number && config('services.gtr.app_live_tracking'))
    <div class="trk-share-card">
      <div>
        <div class="tk">Live Tracking</div>
        <div class="tt">Bagikan link agar keluarga bisa memantau posisimu saat race day.</div>
      </div>
      <div class="tb">
        <a href="{{ route('gtr.track', $reg->bib_number) }}" class="tb-open">Buka</a>
        <button type="button" class="tb-share" data-share-url="{{ route('gtr.track', $reg->bib_number) }}" data-share-title="Lacak {{ $reg->full_name }} di Gerung Trail Run 2026">Bagikan</button>
      </div>
    </div>
  @endif

  <div class="evd-actions">
    @php
      $resultReady = \App\Models\GtrSetting::first()?->results_published && $reg->bib_number
        && $reg->result && in_array($reg->result->status, ['finisher', 'dnf', 'dq'], true);
      $photoCount = $reg->bib_number ? $reg->photos()->count() : 0;
    @endphp
    @if($resultReady)
      <a class="evd-act" href="{{ route('gtr.result', $reg->bib_number) }}">Result</a>
    @else
      <button type="button" class="evd-act" disabled title="Tersedia setelah hasil resmi diumumkan">Result</button>
    @endif
    @if($photoCount)
      <a class="evd-act" href="{{ route('gtr.account.race.photos', $reg) }}">My Photo ({{ $photoCount }})</a>
    @else
      <button type="button" class="evd-act" disabled title="Foto muncul setelah panitia menandai BIB-mu">My Photo</button>
    @endif
  </div>
@else
  @php
    $base = $reg->baseAmount();
    $fee = $reg->serviceFee();
    $total = $base + $fee;
  @endphp
  <div class="pay-cta">
    <div class="pay-cta-h">Selesaikan Pembayaran</div>
    <p>E-ticket (QR code) akan muncul di sini setelah pembayaran kamu lunas.</p>
    <div class="pay-cta-amt">IDR {{ number_format($total, 0, ',', '.') }}@if($fee > 0) <span class="info-i">?<span class="bubble">Sudah termasuk biaya layanan IDR {{ number_format($fee, 0, ',', '.') }} — biaya untuk pengelolaan sistem pendaftaran, e-ticket, dan dukungan peserta.</span></span>@endif</div>
    @if(\App\Models\GtrSetting::registrationClosed())
      <div class="pay-cta-btn" style="opacity:.6;cursor:default;text-align:center">Pendaftaran &amp; pembayaran sudah ditutup</div>
    @else
    <form method="POST" action="{{ route('gtr.payment.pay', $reg) }}">
      @csrf
      <button type="submit" class="pay-cta-btn">{{ $reg->payment_status === 'cancelled' ? 'Bayar Lagi' : 'Bayar Sekarang' }}</button>
    </form>
    @endif
  </div>
@endif

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
