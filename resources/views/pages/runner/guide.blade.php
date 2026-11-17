@extends('layouts.runner-app')
@section('title', 'Race Guide & Dokumen — Runger')
@section('app-title', 'Guide')

@push('styles')
@verbatim
<style>
  .g-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin:22px 2px 12px}
  .sec-sub + .g-head{margin-top:0}
  .g-head .k{display:inline-flex;align-items:center;gap:8px;font-weight:800;font-size:11.5px;letter-spacing:.14em;text-transform:uppercase;color:var(--blue)}
  .g-head .k::before{content:'';width:14px;height:2px;border-radius:2px;background:var(--blue)}
  .g-head .n{font-weight:700;font-size:11px;color:var(--mute)}

  /* kartu ringkas race guide — viewer dibuka di halaman tersendiri */
  .rg-card{position:relative;display:flex;align-items:center;gap:14px;overflow:hidden;isolation:isolate;border-radius:18px;padding:15px 16px;color:#fff;margin-bottom:6px;
    background:radial-gradient(220px 140px at 100% 0%,rgba(229,57,53,.45),transparent 70%),linear-gradient(135deg,#1A1A1A 0%,#0A0A0A 100%);
    box-shadow:0 12px 28px rgba(10,10,10,.3);transition:transform .15s}
  .rg-card:active{transform:translateY(1px)}
  .rg-ic{width:46px;height:46px;flex-shrink:0;border-radius:13px;display:grid;place-items:center;background:var(--red);box-shadow:0 6px 16px rgba(229,57,53,.4)}
  .rg-ic svg{width:23px;height:23px}
  .rg-txt{flex:1;min-width:0}
  .rg-txt .t{font-weight:900;font-size:16px;line-height:1.1;text-transform:uppercase}
  .rg-txt .d{font-size:11.5px;color:rgba(255,255,255,.7);margin-top:4px;line-height:1.35}
  .rg-go{flex-shrink:0;display:inline-flex;align-items:center;gap:6px;padding:9px 12px;border-radius:999px;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.22);
    font-weight:800;font-size:10.5px;letter-spacing:.04em;text-transform:uppercase}
  .rg-card.off{background:var(--card);color:var(--text);border:1px solid var(--line);box-shadow:0 6px 20px rgba(15,24,48,.05)}
  .rg-card.off .rg-ic{background:#EEF2FF;color:var(--blue);box-shadow:none}
  .rg-card.off .d{color:var(--mute)}

  /* dokumen peserta */
  .doc{display:flex;align-items:center;gap:13px}
  .doc-ic{width:44px;height:52px;flex-shrink:0;border-radius:9px;display:grid;place-items:center;position:relative;
    background:linear-gradient(160deg,#FFEBEE,#FFCDD2);color:var(--red-deep);border:1px solid #F8BBD0}
  .doc-ic svg{width:22px;height:22px}
  .doc-ic b{position:absolute;bottom:4px;font-size:7.5px;font-weight:900;letter-spacing:.08em}
  .doc-txt{flex:1;min-width:0}
  .doc-txt .nm{font-weight:800;font-size:14.5px;line-height:1.25}
  .doc-txt .sb{font-size:11.5px;color:var(--mute);margin-top:3px;line-height:1.35}
  .doc-dl{display:flex;gap:7px;margin-top:12px}
  .doc-dl a{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 12px;border-radius:10px;
    font-weight:800;font-size:11px;letter-spacing:.04em;text-transform:uppercase;border:1px solid var(--line);color:var(--blue);background:#F5F7FC}
  .doc-dl a.main{flex:1;background:var(--blue);border-color:var(--blue);color:#fff}
  .doc-dl svg{width:14px;height:14px}
  .g-note{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:12px;margin-bottom:14px;
    background:#EEF2FF;border:1px solid #D6DEFA;color:#2B3A75;font-size:12px;line-height:1.5}
  .g-note svg{width:16px;height:16px;flex-shrink:0;margin-top:1px;color:var(--blue)}
</style>
@endverbatim
@endpush

@php
  $dlIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v12M6 11l6 6 6-6M4 20h16"/></svg>';
  $bookIcon = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5V5a2 2 0 0 1 2-2h13v15H6.5a2.5 2.5 0 0 0 0 5H19"/><path d="M9 7h6M9 11h4"/></svg>';
@endphp

@section('content')
<div class="sec-title">Race Guide &amp; Dokumen</div>
<div class="sec-sub">Panduan resmi race day dan dokumen yang perlu kamu siapkan sebelum lomba.</div>

{{-- ===== Race Guide (ringkas) ===== --}}
<div class="g-head"><span class="k">Race Guide</span></div>
@if($published)
  <a class="rg-card" href="{{ route('gtr.account.guide.view') }}">
    <span class="rg-ic">{!! $bookIcon !!}</span>
    <span class="rg-txt">
      <span class="t">Official Race Guide</span>
      <span class="d">{{ $pageCount }} halaman{{ $updatedAt ? ' · diperbarui ' . $updatedAt->timezone('Asia/Makassar')->translatedFormat('d M Y') : '' }}</span>
    </span>
    <span class="rg-go">Baca →</span>
  </a>
@else
  <div class="rg-card off">
    <span class="rg-ic">{!! $bookIcon !!}</span>
    <span class="rg-txt">
      <span class="t">Segera Hadir</span>
      <span class="d">Race guide resmi sedang disiapkan panitia. Sementara itu, baca <a href="{{ route('gtr.rules') }}" style="color:var(--blue);font-weight:700">Rules &amp; Regulations</a>.</span>
    </span>
  </div>
@endif

{{-- ===== Dokumen Peserta ===== --}}
<div class="g-head"><span class="k">Dokumen Peserta</span>@if($docs->isNotEmpty())<span class="n">{{ $docs->count() }} dokumen</span>@endif</div>

@if($docs->isNotEmpty())
  @foreach($docs as $d)
    <div class="card">
      <div class="doc">
        <div class="doc-ic">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
          <b>PDF</b>
        </div>
        <div class="doc-txt">
          <div class="nm">{{ $d->nav_label }}</div>
          @if($d->title_id !== $d->nav_label)<div class="sb">{{ $d->title_id }}</div>@endif
        </div>
      </div>
      <div class="doc-dl">
        <a class="main" href="{{ route('gtr.doc', [$d->slug, 'id']) }}">{!! $dlIcon !!} Unduh · Indonesia</a>
        @if($d->has_en && $d->pdf_en)
          <a href="{{ route('gtr.doc', [$d->slug, 'en']) }}" title="{{ $d->title('en') }} (English)">{!! $dlIcon !!} EN</a>
        @endif
      </div>
    </div>
  @endforeach
  <div class="g-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
    <span>Unduh, cetak, lalu isi &amp; tanda tangani dokumen yang diperlukan. Cek Race Guide untuk dokumen apa saja yang wajib dibawa saat pengambilan race pack.</span>
  </div>
@else
  <div class="card"><div class="empty">Belum ada dokumen peserta yang dipublikasikan panitia.</div></div>
@endif
@endsection
