@extends('layouts.gtr')

@section('title', 'Rules And Regulation — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@section('content')
<section class="block" id="rules" style="padding-top:48px">
  <div class="wrap">
    <div class="block-head">
      <div class="eye">Race Guidelines</div>
      <h2>Rules And Regulation</h2>
      <p>Aturan dasar yang wajib dipatuhi seluruh peserta. Detail teknis lengkap akan dibagikan saat technical meeting.</p>
    </div>
    @if(($rules ?? collect())->isNotEmpty())
    <div class="rules-list">
      @foreach($rules as $i => $rule)
      <div class="rule"><div class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</div><div><h4>{{ $rule->title }}</h4><p>{{ $rule->content }}</p></div></div>
      @endforeach
    </div>
    @else
    <p style="text-align:center;color:var(--text-soft);font-size:14.5px">Aturan akan segera diupdate.</p>
    @endif
  </div>
</section>
@endsection
