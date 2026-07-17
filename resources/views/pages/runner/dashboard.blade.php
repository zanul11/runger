@extends('layouts.gtr')
@section('title', 'Akun Peserta — Gerung Trail Run')
@section('bodyClass', 'gtr-sub')

@push('styles')
@verbatim
<style>
  .dash{max-width:1100px;margin:0 auto;padding:0 16px}
  .dash-head{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:8px}
  .dash-hi{font-family:'Poppins',sans-serif;font-weight:900;font-size:34px;line-height:1;letter-spacing:-.01em}
  .dash-sub{color:var(--text-soft);font-size:14px;margin-top:8px}
  .dash-logout{background:transparent;border:1px solid rgba(255,255,255,.25);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:12px;letter-spacing:.04em;text-transform:uppercase;padding:11px 18px;border-radius:999px;cursor:pointer;transition:background .2s}
  .dash-logout:hover{background:rgba(255,255,255,.1)}
  .dash-card{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:22px;margin-top:18px}
  .dash-card h3{font-family:'Poppins',sans-serif;font-weight:800;font-size:13px;letter-spacing:.14em;text-transform:uppercase;color:var(--red);margin-bottom:16px}
  .reg-row{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid var(--line);flex-wrap:wrap}
  .reg-row:last-child{border-bottom:none}
  .reg-name{font-family:'Poppins',sans-serif;font-weight:800;font-size:17px}
  .reg-meta{font-size:12.5px;color:var(--text-mute);margin-top:3px}
  .badge{font-family:'Poppins',sans-serif;font-weight:700;font-size:10.5px;letter-spacing:.1em;text-transform:uppercase;padding:6px 12px;border-radius:999px}
  .badge.pending{background:rgba(251,191,36,.14);color:#fbbf24;border:1px solid rgba(251,191,36,.4)}
  .badge.paid,.badge.confirmed{background:rgba(34,197,94,.14);color:#22c55e;border:1px solid rgba(34,197,94,.4)}
  .pick-grid{display:grid;grid-template-columns:1fr;gap:14px}
  .pick{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:16px 18px;border:1px solid var(--line);border-radius:14px;background:rgba(255,255,255,.02)}
  .pick .d{font-family:'Poppins',sans-serif;font-weight:900;font-size:24px;line-height:1}
  .pick .n{font-size:13px;color:var(--text-soft);margin-top:4px}
  .pick .price{font-family:'Poppins',sans-serif;font-weight:800;font-size:14px;color:var(--red);margin-top:6px}
  .pick-btn{background:var(--red);color:#fff;font-family:'Poppins',sans-serif;font-weight:700;font-size:12px;letter-spacing:.04em;text-transform:uppercase;padding:12px 20px;border-radius:10px;border:none;cursor:pointer;white-space:nowrap;transition:background .2s}
  .pick-btn:hover{background:var(--red-deep)}
  .pick-done{font-family:'Poppins',sans-serif;font-weight:700;font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#22c55e;white-space:nowrap}
  .dash-ok{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.4);color:#22c55e;padding:13px 16px;border-radius:12px;font-size:14px;margin-bottom:18px}
  @media (min-width:760px){ .pick-grid{grid-template-columns:1fr 1fr} }
</style>
@endverbatim
@endpush

@section('content')
<section class="block" style="padding-top:36px">
  <div class="dash">
    @if(session('success'))
      <div class="dash-ok">{{ session('success') }}</div>
    @endif

    <div class="dash-head">
      <div>
        <div class="dash-hi">Halo, {{ $runner->first_name }} 👋</div>
        <div class="dash-sub">{{ $runner->email }} · {{ $runner->nationality }}</div>
      </div>
      <form method="POST" action="{{ route('gtr.logout') }}">
        @csrf
        <button type="submit" class="dash-logout">Keluar</button>
      </form>
    </div>

    <div class="dash-card">
      <h3>Pendaftaran Saya</h3>
      @forelse($registrations as $reg)
        <div class="reg-row">
          <div>
            <div class="reg-name">{{ $reg->category->name ?? '-' }} · {{ $reg->category->distance ?? '' }}</div>
            <div class="reg-meta">Didaftarkan {{ optional($reg->registered_at)->translatedFormat('d M Y') }}@if($reg->bib_number) · BIB {{ $reg->bib_number }}@endif</div>
          </div>
          <span class="badge {{ $reg->status }}">{{ ucfirst($reg->status) }}</span>
        </div>
      @empty
        <div class="reg-meta">Belum ada pendaftaran. Pilih kategori di bawah untuk mendaftar.</div>
      @endforelse
    </div>

    <div class="dash-card">
      <h3>Daftar Kategori</h3>
      <div class="pick-grid">
        @foreach($categories as $c)
          <div class="pick">
            <div>
              <div class="d">{{ $c->distance }}</div>
              <div class="n">{{ $c->name }}</div>
              <div class="price">{{ $c->current_price_formatted }}</div>
            </div>
            @if(in_array($c->id, $registeredIds))
              <span class="pick-done">✓ Terdaftar</span>
            @else
              <form method="POST" action="{{ route('gtr.dashboard.register', $c) }}">
                @csrf
                <button type="submit" class="pick-btn">Daftar</button>
              </form>
            @endif
          </div>
        @endforeach
      </div>
    </div>
  </div>
</section>
@endsection
