@extends('layouts.gtr')

@section('title', 'Lacak Pelari — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@push('styles')
<style>
  .trk-form{display:flex;gap:10px;margin-top:22px;max-width:560px}
  .trk-form input{flex:1;min-width:0;padding:15px 18px;border-radius:999px;border:1px solid var(--line-strong);background:var(--bg-soft);color:#fff;font:inherit;font-size:15px}
  .trk-form input:focus{outline:none;border-color:var(--red)}
  .trk-list{display:flex;flex-direction:column;gap:10px;margin-top:22px;max-width:560px}
  .trk-item{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:16px;background:var(--bg-soft);border:1px solid var(--line);transition:border-color .2s}
  .trk-item:hover{border-color:var(--line-strong)}
  .trk-bib{min-width:62px;text-align:center;padding:7px 8px;border-radius:10px;background:#fff;color:#101010;font-family:'Archivo',sans-serif;font-weight:900;font-size:14px}
  .trk-item b{display:block;font-family:'Archivo',sans-serif;font-weight:800;font-size:15px}
  .trk-item small{color:var(--text-mute);font-size:12.5px}
  .trk-empty{margin-top:18px;color:var(--text-soft);font-size:14px}
</style>
@endpush

@section('content')
<section class="block" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Live Tracking</div>
      <h2>Lacak Pelari</h2>
      <p>Masukkan nomor BIB atau nama peserta untuk melihat posisi & waktu lewat tiap pos saat race day.</p>
    </div>

    <form class="trk-form" method="GET" action="{{ route('gtr.track.search') }}">
      <input type="search" name="q" value="{{ $q }}" placeholder="Nomor BIB atau nama (min. 3 huruf)" autofocus>
      <button type="submit" class="btn-primary">Cari <span class="arr">→</span></button>
    </form>

    @if($q !== '')
      @if($results->count())
        <div class="trk-list">
          @foreach($results as $r)
            <a class="trk-item" href="{{ route('gtr.track', $r->bib_number) }}">
              <span class="trk-bib">{{ $r->bib_number }}</span>
              <span><b>{{ $r->full_name }}</b><small>{{ $r->category->distance ?? '' }}{{ $r->club ? ' · ' . $r->club : '' }}</small></span>
            </a>
          @endforeach
        </div>
      @else
        <p class="trk-empty">Peserta tidak ditemukan. Pastikan nomor BIB benar, atau ketik minimal 3 huruf nama.</p>
      @endif
    @endif
  </div>
</section>
@endsection
