@php($logo = $sp->logo_url)
<{{ $sp->link ? 'a' : 'div' }} class="sponsor-item"
  @if($sp->link) href="{{ $sp->link }}" target="_blank" rel="noopener" @endif
  title="{{ $sp->name }}">
  <span class="sponsor-slot">
    @if($logo)<img src="{{ $logo }}" alt="{{ $sp->name }}" loading="lazy" decoding="async">@else{{ $sp->name }}@endif
  </span>
  @if($sp->role)<span class="sponsor-role">{{ $sp->role }}</span>@endif
</{{ $sp->link ? 'a' : 'div' }}>
