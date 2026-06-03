@extends('layouts.app')
@section('title', 'Galeri — ' . \App\Models\Setting::get('site.name'))

@push('styles')
<style>
  .gph{padding:36px 16px 24px;border-bottom:1px solid var(--line)}
  .gph-inner{max-width:1320px;margin:0 auto}
  .gph-eye{font-family:'JetBrains Mono',monospace;font-size:11px;letter-spacing:.22em;text-transform:uppercase;color:var(--volt);margin-bottom:10px;display:inline-flex;align-items:center;gap:10px}
  .gph-eye::before{content:'';width:22px;height:1px;background:var(--volt)}
  .gph-title{font-family:'Bebas Neue',sans-serif;font-size:44px;line-height:.95}
  .gph-title em{font-style:normal;color:var(--volt)}
  .gph-sub{margin-top:6px;font-size:14px;opacity:.7;max-width:520px}
  .gal-wrap{max-width:1320px;margin:0 auto;padding:20px 16px 60px}
  .gal-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:6px}
  .gal-cell{aspect-ratio:1;background-size:cover;background-position:center;border-radius:3px;border:1px solid var(--line);transition:transform .2s, border-color .2s;cursor:pointer;position:relative;overflow:hidden}
  .gal-cell:hover{transform:scale(1.02);border-color:rgba(226,240,84,.4)}
  .gal-tag{position:absolute;top:6px;left:6px;background:rgba(0,0,0,.65);color:#fff;padding:3px 7px;font-family:'JetBrains Mono',monospace;font-size:8.5px;letter-spacing:.12em;text-transform:uppercase;border-radius:2px}
  @media (min-width:600px){.gal-grid{grid-template-columns:repeat(3,1fr);gap:8px}}
  @media (min-width:900px){.gal-grid{grid-template-columns:repeat(4,1fr);gap:10px}}
  @media (min-width:1200px){.gal-grid{grid-template-columns:repeat(5,1fr);gap:10px}}
  @media (min-width:820px){.gph{padding:48px 32px 32px}.gph-title{font-size:64px}.gal-wrap{padding:24px 32px 80px}}
</style>
@endpush

@section('body')
<x-site-nav active="gallery" />

<header class="gph">
  <div class="gph-inner">
    <div class="gph-eye">Galeri Runger · {{ $items->count() }} foto</div>
    <h1 class="gph-title">MOMEN <em>SQUAD.</em></h1>
    <p class="gph-sub">Foto-foto dari lari mingguan, long run, dan event komunitas.</p>
  </div>
</header>

<section class="gal-wrap">
  <div class="gal-grid">
    @foreach($items as $it)
      <div class="gal-cell" style="background-image:url('{{ media_url($it->image) }}')" title="{{ $it->caption }}">
        @if($it->tag)<span class="gal-tag">● {{ $it->tag }}</span>@endif
      </div>
    @endforeach
  </div>
</section>

<x-site-footer />
@endsection
