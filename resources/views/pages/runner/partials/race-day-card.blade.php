{{--
  Kartu "Race Day" (App\Support\RaceDayCard). Param: $reg, $card (['mode','when']), $setting (GtrSetting|null),
  $compact (true = versi Home: ringkas + tombol ke detail; false = versi Detail Event: lengkap + QR).
--}}
@php
  $compact = $compact ?? false;
  $isPack = $card['mode'] === \App\Support\RaceDayCard::RACEPACK;
  $title = $isPack
    ? match ($card['when']) {
        'tomorrow' => 'Besok mulai ambil race pack',
        'last' => 'Hari terakhir ambil race pack!',
        default => 'Hari ini ambil race pack',
      }
    : ($card['when'] === 'tomorrow' ? 'Besok race day!' : 'Hari ini race day!');
  $eyebrow = $isPack ? 'Race Pack Collection' : 'Race Day · ' . ($reg->category->distance ?? 'GTR');
  $contact = $setting?->race_contact;
  $kuasa = $isPack && ! $compact
    && \App\Models\GtrDocument::where('slug', 'surat-kuasa')->where('is_published', true)->whereNotNull('pdf_id')->exists();
@endphp

@once
@push('styles')
<style>
  .rdc{position:relative;overflow:hidden;isolation:isolate;border-radius:20px;padding:18px;margin-bottom:16px;color:#fff;
    background:radial-gradient(220px 170px at 100% 0%,rgba(229,57,53,.5),transparent 70%),linear-gradient(150deg,#1a1d29,#0b0c12);
    box-shadow:0 16px 36px rgba(10,12,22,.4)}
  .rdc.pack{background:radial-gradient(220px 170px at 100% 0%,rgba(63,98,216,.6),transparent 70%),linear-gradient(150deg,#16204a,#0b0f22)}
  .rdc-k{display:inline-flex;align-items:center;gap:7px;font-weight:800;font-size:9.5px;letter-spacing:.16em;text-transform:uppercase;color:#FF8A80}
  .rdc.pack .rdc-k{color:#A9BCFF}
  .rdc-k::before{content:'';width:7px;height:7px;border-radius:50%;background:currentColor;box-shadow:0 0 0 3px rgba(255,255,255,.12);animation:rdc-pulse 1.6s ease-in-out infinite}
  @keyframes rdc-pulse{50%{opacity:.35}}
  @media (prefers-reduced-motion:reduce){.rdc-k::before{animation:none}}
  .rdc-t{font-weight:900;font-size:22px;line-height:1.1;margin-top:6px}
  .rdc-bib{display:flex;align-items:baseline;gap:10px;margin-top:12px}
  .rdc-bib small{font-weight:800;font-size:10px;letter-spacing:.14em;text-transform:uppercase;opacity:.6}
  .rdc-bib b{font-weight:900;font-size:40px;line-height:.9;letter-spacing:.02em;font-variant-numeric:tabular-nums}
  .rdc-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}
  .rdc-grid div{padding:9px 11px;border-radius:12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);min-width:0}
  .rdc-grid div.wide{grid-column:1 / -1}
  .rdc-grid small{display:block;font-weight:700;font-size:9px;letter-spacing:.1em;text-transform:uppercase;opacity:.6}
  .rdc-grid b{display:block;font-weight:800;font-size:13.5px;margin-top:2px;line-height:1.35;word-break:break-word}
  .rdc-grid b span{display:block;font-weight:500;font-size:11.5px;opacity:.75;margin-top:2px}
  .rdc-warn{margin-top:12px;padding:10px 12px;border-radius:12px;background:rgba(245,158,11,.16);border:1px solid rgba(245,158,11,.4);font-size:12.5px;line-height:1.45;color:#FDE68A}
  .rdc-note{margin-top:12px;font-size:12.5px;line-height:1.5;opacity:.85}
  .rdc-qr{margin-top:14px;background:#fff;border-radius:16px;padding:18px;display:flex;flex-direction:column;align-items:center;gap:10px}
  .rdc-qr img{width:230px;max-width:78%;height:auto;aspect-ratio:1}
  .rdc-qr .qcap{font-weight:700;font-size:13px;color:#101010;letter-spacing:.02em}
  .rdc-qr .qhint{font-size:11.5px;color:#667;text-align:center}
  .rdc-acts{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
  .rdc-acts a{flex:1 1 auto;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:11px 14px;border-radius:12px;
    font-weight:800;font-size:11.5px;letter-spacing:.03em;text-transform:uppercase;background:rgba(255,255,255,.1);color:#fff;white-space:nowrap}
  .rdc-acts a.pri{background:var(--red);box-shadow:0 8px 18px rgba(229,57,53,.35)}
  .rdc.pack .rdc-acts a.pri{background:var(--blue-bright);box-shadow:0 8px 18px rgba(63,98,216,.35)}
  .rdc-wxw{margin-top:12px}
  .rdc-wxw:empty{display:none}
  .rdc-wx{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14)}
</style>
@endpush
@endonce

<section class="rdc {{ $isPack ? 'pack' : '' }}" aria-label="{{ $title }}">
  <div class="rdc-k">{{ $eyebrow }}</div>
  <div class="rdc-t">{{ $title }}</div>

  @if($isPack)
    <div class="rdc-grid">
      @if($setting?->racepack_schedule)<div class="wide"><small>Jadwal</small><b>{{ $setting->racepack_schedule }}</b></div>@endif
      @if($setting?->racepack_location)
        <div class="wide"><small>Lokasi</small><b>{{ $setting->racepack_location }}@if(! $compact && $setting->racepack_address)<span>{{ $setting->racepack_address }}</span>@endif</b></div>
      @endif
    </div>
    @if(! $compact && $setting?->racepack_note)<div class="rdc-note">{{ $setting->racepack_note }}</div>@endif

    @unless($compact)
      <div class="rdc-qr">
        <img src="{{ $reg->qrDataUri() }}" alt="QR e-ticket {{ $reg->nomor_registrasi }}" width="230" height="230">
        <div class="qcap">{{ $reg->nomor_registrasi }}</div>
        <div class="qhint">Tunjukkan QR ini ke petugas race pack. Tetap tampil walau tanpa sinyal.</div>
      </div>
    @endunless
  @else
    @if($reg->bib_number)
      <div class="rdc-bib"><small>BIB</small><b>{{ $reg->bib_number }}</b></div>
    @endif
    <div class="rdc-grid">
      @if($setting?->assembly_time)<div><small>Jam kumpul</small><b>{{ $setting->assembly_time }}</b></div>@endif
      @if($reg->category?->start_time)<div><small>Start {{ $reg->category->distance }}</small><b>{{ $reg->category->start_time }}</b></div>@endif
      @if($setting?->location_text)<div class="wide"><small>Lokasi start</small><b>{{ $setting->location_text }}</b></div>@endif
    </div>
    @unless($reg->racepack_collected_at)
      <div class="rdc-warn">Race pack kamu belum tercatat diambil. Segera hubungi panitia{{ $contact ? ' di ' . $contact : '' }} sebelum start.</div>
    @endunless
    @unless($compact)
      <div class="rdc-wxw">@include('pages.partials.gtr-weather', ['style' => 'chip', 'class' => 'rdc-wx'])</div>
    @endunless
  @endif

  <div class="rdc-acts">
    @if($compact)
      <a class="pri" href="{{ route('gtr.account.race.detail', $reg) }}">{{ $isPack ? 'Buka e-ticket' : 'Detail race' }} →</a>
    @endif
    @if($isPack && $setting?->racepack_maps_url)
      <a href="{{ $setting->racepack_maps_url }}" target="_blank" rel="noopener">📍 Maps</a>
    @endif
    @if($kuasa)
      <a href="{{ route('gtr.doc', ['surat-kuasa', 'id']) }}">Surat kuasa</a>
    @endif
    @if(! $isPack && ! $compact)
      @if(! empty($plan ?? null))
        <a class="pri" href="{{ route('gtr.account.race.paceband', $reg) }}">Pace band</a>
      @endif
      @if($reg->bib_number && config('services.gtr.app_live_tracking'))
        <a href="{{ route('gtr.track', $reg->bib_number) }}">Live tracking</a>
      @endif
    @endif
    @if(! $isPack && $contact)
      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact) }}">📞 Panitia</a>
    @endif
  </div>
</section>
