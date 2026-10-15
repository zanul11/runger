{{--
  Info cuaca race day. $style: 'card' (lengkap) | 'chip' (ringkas untuk header). $theme: 'dark' | 'light'.
  Data dari App\Services\RaceWeather (cache). Tidak tampil apa pun bila data tidak tersedia.
--}}
@php
  $weather = $weather ?? app(\App\Services\RaceWeather::class)->forRace();
  $style = $style ?? 'card';
  $theme = $theme ?? 'light';
@endphp

@if($weather)
  @php
    [$wxLabel, $wxIcon] = \App\Services\RaceWeather::describe($weather['code']);
    $isFc = $weather['mode'] === 'forecast';
    $rainLabel = $isFc ? 'Peluang hujan' : 'Hari hujan*';
  @endphp

  @once
  @push('styles')
  <style>
    .wx{--wx-bg:#fff;--wx-fg:#101010;--wx-mute:#8A8A8A;--wx-line:#EFEFEF;--wx-soft:#F7F7F7;border-radius:20px;padding:16px 18px;background:var(--wx-bg);color:var(--wx-fg);border:1px solid var(--wx-line)}
    .wx.dark{--wx-bg:linear-gradient(160deg,#181818,#111);--wx-fg:#fff;--wx-mute:rgba(255,255,255,.55);--wx-line:rgba(255,255,255,.08);--wx-soft:rgba(255,255,255,.05)}
    .wx-head{display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap}
    .wx-k{font-family:'Archivo','Poppins',sans-serif;font-weight:800;font-size:10px;letter-spacing:.16em;text-transform:uppercase;color:var(--wx-mute)}
    .wx-src{font-size:11px;color:var(--wx-mute)}
    .wx-main{display:flex;align-items:center;gap:14px;margin-top:10px}
    .wx-ic{font-size:40px;line-height:1}
    .wx-temp{font-family:'Archivo','Poppins',sans-serif;font-weight:900;font-size:30px;line-height:1}
    .wx-lab{font-size:12.5px;color:var(--wx-mute);margin-top:4px;font-weight:600}
    .wx-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px}
    .wx-stats div{padding:9px 10px;border-radius:12px;background:var(--wx-soft)}
    .wx-stats small{display:block;font-size:9.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--wx-mute)}
    .wx-stats b{display:block;font-family:'Archivo','Poppins',sans-serif;font-weight:900;font-size:16px;margin-top:2px}
    .wx-hours{display:flex;gap:6px;overflow-x:auto;margin-top:12px;padding-bottom:2px;scrollbar-width:none}
    .wx-hours::-webkit-scrollbar{display:none}
    .wx-h{flex:0 0 auto;min-width:54px;text-align:center;padding:8px 6px;border-radius:12px;background:var(--wx-soft);font-size:11px}
    .wx-h b{display:block;font-family:'Archivo','Poppins',sans-serif;font-weight:900;font-size:14px}
    .wx-h .r{color:#2EA6E6;font-weight:700}
    .wx-tips{list-style:none;display:flex;flex-direction:column;gap:6px;margin-top:12px;padding:0}
    .wx-tips li{display:flex;gap:8px;font-size:12.5px;line-height:1.45}
    .wx-tips li::before{content:'🎒';flex-shrink:0}
    .wx-note{font-size:10.5px;color:var(--wx-mute);margin-top:10px;line-height:1.4}
    /* chip header */
    .wx-chip{display:inline-flex;align-items:center;gap:8px;padding:10px 14px;border-radius:999px;font-size:12.5px;font-weight:600;line-height:1}
    .wx-chip b{font-weight:800}
    .wx-chip .ic{font-size:15px}
    .wx-chip .sep{opacity:.45}
  </style>
  @endpush
  @endonce

  @if($style === 'chip')
    <span class="wx-chip {{ $class ?? '' }}" title="{{ $isFc ? 'Ramalan cuaca race day' : 'Rata-rata cuaca ' . $weather['years'] . ' tahun terakhir di tanggal race' }}">
      <span class="ic">{{ $wxIcon }}</span>
      <b>{{ $weather['temp_min'] }}–{{ $weather['temp_max'] }}°C</b>
      <span class="sep">·</span>
      <span>{{ $isFc ? 'Hujan ' . $weather['rain'] . '%' : $wxLabel }}</span>
      <span class="sep">·</span>
      <span>{{ $isFc ? 'Ramalan race day' : 'Biasanya di race day' }}</span>
    </span>
  @else
    <div class="wx {{ $theme }}">
      <div class="wx-head">
        <span class="wx-k">Cuaca Race Day · {{ $weather['window'] }} WITA</span>
        <span class="wx-src">{{ $isFc ? 'Ramalan · diperbarui ' . $weather['updated'] : 'Rata-rata ' . $weather['years'] . ' tahun terakhir' }}</span>
      </div>
      <div class="wx-main">
        <span class="wx-ic">{{ $wxIcon }}</span>
        <div>
          <div class="wx-temp">{{ $weather['temp_min'] }}–{{ $weather['temp_max'] }}°C</div>
          <div class="wx-lab">{{ $wxLabel }} · {{ $weather['date'] }}</div>
        </div>
      </div>
      <div class="wx-stats">
        <div><small>{{ $rainLabel }}</small><b>{{ $weather['rain'] }}%</b></div>
        <div><small>Angin</small><b>{{ $weather['wind'] }} km/j</b></div>
        <div><small>Matahari terbit</small><b>{{ $weather['sunrise'] ?? '-' }}</b></div>
      </div>
      @if($isFc && count($weather['hours']))
        <div class="wx-hours">
          @foreach($weather['hours'] as $h)
            @php [, $hi] = \App\Services\RaceWeather::describe($h['code']); @endphp
            <div class="wx-h">{{ $h['time'] }}<span style="display:block;font-size:17px;margin:3px 0">{{ $hi }}</span><b>{{ $h['temp'] }}°</b><span class="r">{{ $h['rain'] }}%</span></div>
          @endforeach
        </div>
      @endif
      <ul class="wx-tips">
        @foreach($weather['tips'] as $tip)<li>{{ $tip }}</li>@endforeach
      </ul>
      @unless($isFc)
        <p class="wx-note">*Persentase hari dengan hujan ≥1 mm di sekitar tanggal race, 5 tahun terakhir. Ramalan per jam tampil otomatis mulai {{ $weather['forecast_from'] }}.</p>
      @endunless
    </div>
  @endif
@endif
