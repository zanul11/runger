@php
  $logo = $sp->logo_url;
  $tag = $sp->link ? 'a' : 'div';
  // Monogram (maks. 2 huruf) untuk sponsor yang belum punya logo.
  $mono = collect(preg_split('/\s+/', trim($sp->name)))->filter()->take(2)
    ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<{{ $tag }} class="sponsor-card{{ $logo ? '' : ' no-logo' }}"
  @if($sp->link) href="{{ $sp->link }}" target="_blank" rel="noopener" @endif
  title="{{ $sp->name }}">
  <span class="sponsor-logo">
    @if($logo)
      <img src="{{ $logo }}" alt="{{ $sp->name }}" loading="lazy" decoding="async">
    @else
      <span class="sponsor-mono" aria-hidden="true">{{ $mono }}</span>
    @endif
  </span>
  <span class="sponsor-name">{{ $sp->name }}</span>
  @if($sp->link)<span class="sponsor-go" aria-hidden="true">↗</span>@endif
</{{ $tag }}>
