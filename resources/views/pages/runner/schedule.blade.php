@extends('layouts.runner-app')
@section('title', 'Jadwal — Runger')

@php
  // "Jumat, 27 November 2026" → chip hari + tanggal; teks lain apa adanya.
  $days = collect($timeline)->map(function ($d, $i) {
      $chip = preg_match('/^(\p{L}+),\s*(\d{1,2})\s+(\p{L}+)\s+(\d{4})/u', $d['date'], $m)
        ? ['dow' => mb_strtoupper(mb_substr($m[1], 0, 3)), 'num' => $m[2], 'month' => mb_strtoupper($m[3] . ' ' . $m[4])]
        : ['dow' => 'HARI', 'num' => $i + 1, 'month' => null];

      return $d + ['chip' => $chip, 'key' => 'd' . $i];
  });
  $active = $days->firstWhere('date', $today) ?? $days->first();
@endphp

@push('styles')
@verbatim
<style>
  .sc-month{font-weight:900;font-size:24px;letter-spacing:-.01em;color:var(--text);margin:2px 2px 12px}
  .sc-days{display:flex;gap:8px;overflow-x:auto;padding:2px 2px 4px;margin-bottom:14px;scrollbar-width:none}
  .sc-days::-webkit-scrollbar{display:none}
  .sc-day{flex:0 0 auto;width:58px;padding:9px 0 10px;border-radius:15px;border:none;cursor:pointer;text-align:center;font-family:'Poppins',sans-serif;
    background:#101010;color:#fff;transition:background .2s,transform .15s}
  .sc-day small{display:block;font-weight:700;font-size:9.5px;letter-spacing:.1em;opacity:.7}
  .sc-day b{display:block;font-weight:900;font-size:20px;line-height:1.1;margin-top:2px}
  .sc-day.on{background:var(--red);box-shadow:0 8px 18px rgba(229,57,53,.35)}
  .sc-day .flag{display:block;font-weight:800;font-size:7.5px;letter-spacing:.12em;margin-top:2px;color:#FFD3CF}
  .sc-day:not(.on) .flag{color:#FF8A80}
  .sc-date{font-weight:800;font-size:13px;color:var(--soft);margin:0 2px 10px}
  .sc-panel{display:none}
  .sc-panel.on{display:block}
  .sc-item{display:flex;align-items:center;gap:14px;padding:15px 16px;margin-bottom:10px;border-radius:18px;background:#fff;border:1px solid var(--line);box-shadow:0 6px 16px rgba(15,24,48,.04)}
  .sc-item.mine{border-left:4px solid var(--red)}
  .sc-time{flex-shrink:0;min-width:74px;font-weight:900;font-size:14px;color:var(--text);font-variant-numeric:tabular-nums}
  .sc-body{flex:1;min-width:0}
  .sc-body b{display:block;font-weight:800;font-size:14px;line-height:1.35;color:var(--text)}
  .sc-body span{display:block;font-size:12px;color:var(--soft);margin-top:2px}
  .sc-tags{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px}
  .sc-tags i{font-style:normal;font-weight:800;font-size:10px;padding:3px 9px;border-radius:999px;background:#101010;color:#fff}
  .sc-empty{padding:26px 18px;border-radius:18px;background:#fff;border:1px solid var(--line);text-align:center;font-size:13px;color:var(--soft);line-height:1.55}
</style>
@endverbatim
@endpush

@section('content')
@if($days->isEmpty())
  <div class="sec-title">Jadwal</div>
  <div class="sc-empty">Jadwal acara akan diumumkan panitia menjelang race day.</div>
@else
  <div class="sc-month" data-sc-month>{{ $active['chip']['month'] ?? 'Jadwal Acara' }}</div>

  <div class="sc-days" role="tablist">
    @foreach($days as $d)
      <button type="button" role="tab" @class(['sc-day', 'on' => $d['key'] === $active['key']]) data-sc="{{ $d['key'] }}" data-month="{{ $d['chip']['month'] }}">
        <small>{{ $d['chip']['dow'] }}</small><b>{{ $d['chip']['num'] }}</b>@if($d['race'])<span class="flag">RACE</span>@endif
      </button>
    @endforeach
  </div>

  @foreach($days as $d)
    <div @class(['sc-panel', 'on' => $d['key'] === $active['key']]) data-sc-panel="{{ $d['key'] }}">
      <div class="sc-date">{{ $d['date'] }}</div>
      @foreach($d['items'] as $it)
        @php $mine = ! empty($myCatIds) && ($it['cats'] === [] || array_intersect($it['cats'], $myCatIds)); @endphp
        <div @class(['sc-item', 'mine' => $mine && $it['cats'] !== []])>
          <div class="sc-time">{{ $it['time'] ?: '—' }}</div>
          <div class="sc-body">
            <b>{{ $it['activity'] ?: '-' }}</b>
            @if($it['location'])<span>📍 {{ $it['location'] }}</span>@endif
            @if($it['cats'])
              <div class="sc-tags">
                @foreach($it['cats'] as $cid)
                  @if(isset($cats[$cid]))<i style="background:{{ $cats[$cid]['color'] }}">{{ $cats[$cid]['distance'] }}</i>@endif
                @endforeach
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>
  @endforeach
@endif
@endsection

@push('scripts')
<script>
  (function () {
    var month = document.querySelector('[data-sc-month]');
    document.querySelectorAll('[data-sc]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('[data-sc]').forEach(function (b) { b.classList.toggle('on', b === btn); });
        document.querySelectorAll('[data-sc-panel]').forEach(function (p) { p.classList.toggle('on', p.dataset.scPanel === btn.dataset.sc); });
        if (month && btn.dataset.month) month.textContent = btn.dataset.month;
      });
    });
  })();
</script>
@endpush
