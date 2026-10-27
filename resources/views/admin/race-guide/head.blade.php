{{-- Header slide: logo GTR + nama event, logo Runger di kanan. --}}
<div class="s-head">
  <div class="s-brand">
    <span class="s-logo"><img src="{{ $g['logos']['gtr'] }}" alt="GTR"></span>
    <span class="s-brand-txt">
      <b>{{ $g['event']['title'] }}</b>
      @if($g['event']['eyebrow'])<small>{{ $g['event']['eyebrow'] }}</small>@endif
    </span>
  </div>
  <img class="s-runger" src="{{ $g['logos']['runger'] }}" alt="Runger">
</div>
