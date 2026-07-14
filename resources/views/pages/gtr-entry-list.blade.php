@extends('layouts.gtr')

@section('title', 'Entry List — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@push('styles')
@verbatim
<style>
  .el-toolbar{margin-bottom:16px}
  .el-search{width:100%;background:var(--card);border:1px solid var(--line);color:#fff;border-radius:10px;padding:12px 15px;font-family:inherit;font-size:14px}
  .el-search::placeholder{color:var(--text-mute)}
  .el-search:focus{outline:none;border-color:var(--red)}

  /* Tabs per kategori */
  .el-tabs{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0}
  .el-tab{
    background:var(--card);border:1px solid var(--line);color:var(--text-soft);
    border-radius:999px;padding:9px 16px;font-family:'Archivo',sans-serif;font-weight:700;
    font-size:12.5px;letter-spacing:.02em;cursor:pointer;display:inline-flex;align-items:center;gap:7px
  }
  .el-tab:hover{border-color:rgba(255,255,255,.28);color:#fff}
  .el-tab.active{background:var(--red);border-color:var(--red);color:#fff}
  .el-tab .el-count{
    font-family:'JetBrains Mono',monospace;font-size:10.5px;font-weight:700;
    background:rgba(255,255,255,.14);border-radius:999px;padding:1px 7px;line-height:1.6
  }
  .el-tab.active .el-count{background:rgba(0,0,0,.22)}

  /* Tabel */
  .el-table-wrap{border:1px solid var(--line);border-radius:14px;overflow:hidden;background:var(--card)}
  table.el-table{width:100%;border-collapse:collapse}
  .el-table thead th{
    background:rgba(255,255,255,.03);color:var(--text-mute);border-bottom:1px solid var(--line);
    text-transform:uppercase;font-size:10px;letter-spacing:.14em;font-family:'Archivo',sans-serif;
    font-weight:700;padding:13px 14px;text-align:left;white-space:nowrap
  }
  .el-table thead th.c,.el-table tbody td.c{text-align:center}
  .el-table tbody td{color:#fff;border-bottom:1px solid var(--line);padding:12px 14px;font-size:14px;vertical-align:middle}
  .el-table tbody tr:last-child td{border-bottom:none}
  .el-table tbody tr:hover td{background:rgba(255,255,255,.025)}
  .el-no{color:var(--text-mute);font-family:'JetBrains Mono',monospace;font-size:12.5px;width:44px}
  .el-bib{font-family:'JetBrains Mono',monospace;font-weight:800;color:var(--red);width:78px}
  .el-bib.empty{color:var(--text-mute);font-weight:600}
  .el-nm{font-weight:600}
  .el-gd{color:var(--text-soft);font-size:13px;white-space:nowrap}
  .el-pay-ok{display:inline-flex;align-items:center;gap:6px;color:#22c55e;font-weight:700;font-size:12.5px;font-family:'Archivo',sans-serif}
  .el-pay-no{display:inline-flex;align-items:center;gap:6px;color:var(--text-mute);font-size:12.5px;font-family:'Archivo',sans-serif}
  .el-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
  .el-dot.ok{background:#22c55e}
  .el-dot.no{background:var(--text-mute)}
  .el-empty{padding:26px;text-align:center;color:var(--text-mute);font-style:italic}
  @media (max-width:560px){
    .el-hide-sm{display:none}
    .el-table tbody td,.el-table thead th{padding:11px 10px}
  }
</style>
@endverbatim
@endpush

@section('content')
<section class="block" style="padding-top:48px">
  <div class="wrap" style="max-width:920px">
    <div class="block-head">
      <div class="eye">Registered Runners</div>
      <h2>Entry List</h2>
      <p>Pilih tab kategori atau cari nama / nomor BIB. Peserta yang sudah bayar tampil lebih dulu.</p>
    </div>

    <div class="el-toolbar">
      <input id="el-search" class="el-search" type="search" placeholder="Cari nama atau nomor BIB…" autocomplete="off">
    </div>

    <div class="el-tabs" id="el-tabs">
      <button class="el-tab active" data-cat="">Semua
        <span class="el-count">{{ $categories->sum(fn ($c) => $c->registrations->count()) }}</span>
      </button>
      @foreach($categories as $cat)
        <button class="el-tab" data-cat="{{ $cat->slug }}">{{ $cat->distance }}
          <span class="el-count">{{ $cat->registrations->count() }}</span>
        </button>
      @endforeach
    </div>

    <div class="el-table-wrap">
      <table class="el-table">
        <thead>
          <tr>
            <th class="c">No</th>
            <th>BIB</th>
            <th>Nama</th>
            <th class="el-hide-sm">Gender</th>
            <th class="c">Bayar</th>
          </tr>
        </thead>
        <tbody id="el-body">
          @foreach($categories as $cat)
            @foreach($cat->registrations as $reg)
              @php($paid = $reg->payment_status === 'paid')
              <tr data-cat="{{ $cat->slug }}"
                  data-name="{{ \Illuminate\Support\Str::lower($reg->full_name) }}"
                  data-bib="{{ $reg->bib_number }}">
                <td class="c el-no"></td>
                <td class="el-bib {{ $reg->bib_number ? '' : 'empty' }}">{{ $reg->bib_number ?: '-' }}</td>
                <td class="el-nm">{{ $reg->full_name }}</td>
                <td class="el-gd el-hide-sm">{{ $reg->gender ?: '—' }}</td>
                <td class="c">
                  @if($paid)
                    <span class="el-pay-ok"><span class="el-dot ok"></span>Lunas</span>
                  @else
                    <span class="el-pay-no"><span class="el-dot no"></span>Belum</span>
                  @endif
                </td>
              </tr>
            @endforeach
          @endforeach
          <tr id="el-empty-row" style="display:none"><td colspan="5" class="el-empty">Peserta tidak ditemukan.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

@push('scripts')
@verbatim
<script>
  (function () {
    var tabs = document.getElementById('el-tabs');
    var search = document.getElementById('el-search');
    var body = document.getElementById('el-body');
    var emptyRow = document.getElementById('el-empty-row');
    var rows = Array.prototype.slice.call(body.querySelectorAll('tr[data-cat]'));
    var activeCat = '';

    function apply() {
      var q = (search.value || '').trim().toLowerCase();
      var n = 0;
      rows.forEach(function (tr) {
        var catOk = !activeCat || tr.getAttribute('data-cat') === activeCat;
        var text = tr.getAttribute('data-name') + ' ' + (tr.getAttribute('data-bib') || '');
        var searchOk = !q || text.toLowerCase().indexOf(q) !== -1;
        var show = catOk && searchOk;
        tr.style.display = show ? '' : 'none';
        if (show) { n++; tr.querySelector('.el-no').textContent = n; }
      });
      emptyRow.style.display = n === 0 ? '' : 'none';
    }

    tabs.addEventListener('click', function (e) {
      var btn = e.target.closest('.el-tab');
      if (!btn) return;
      activeCat = btn.getAttribute('data-cat');
      tabs.querySelectorAll('.el-tab').forEach(function (b) { b.classList.toggle('active', b === btn); });
      apply();
    });
    search.addEventListener('input', apply);

    apply();
  })();
</script>
@endverbatim
@endpush
@endsection
