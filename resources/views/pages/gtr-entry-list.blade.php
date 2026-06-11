@extends('layouts.gtr')

@section('title', 'Entry List — Gerung Trail Run 2026')
@section('bodyClass', 'gtr-sub')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
@verbatim
<style>
  .el-toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:14px}
  .el-toolbar label{font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--text-mute)}
  .el-toolbar select{background:var(--card);border:1px solid var(--line);color:#fff;border-radius:8px;padding:9px 13px;font-family:inherit;font-size:13px;cursor:pointer}

  .el-cat-tag{font-family:'Archivo',sans-serif;font-weight:700;font-size:11px;color:var(--red)}
  .el-pay-ok{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:rgba(34,197,94,.15);color:#22c55e;border:1px solid rgba(34,197,94,.45);font-weight:800;font-size:13px}
  .el-pay-no{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:50%;background:rgba(255,255,255,.04);color:var(--text-mute);border:1px solid var(--line);font-size:13px}
  .el-legend{display:flex;gap:18px;justify-content:center;margin-top:16px;font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute)}
  .el-legend span{display:inline-flex;align-items:center;gap:7px}

  /* DataTables dark theme */
  .dataTables_wrapper{color:var(--text-soft);font-size:13px}
  .dataTables_wrapper .dataTables_filter{margin-bottom:12px}
  .dataTables_wrapper .dataTables_filter label{color:var(--text-mute);font-family:'Archivo',sans-serif;font-size:12px}
  .dataTables_wrapper .dataTables_filter input,
  .dataTables_wrapper .dataTables_length select{
    background:var(--card);border:1px solid var(--line);color:#fff;border-radius:8px;padding:9px 13px;font-family:inherit;margin-left:8px
  }
  .dataTables_wrapper .dataTables_filter input::placeholder{color:var(--text-mute)}
  .dataTables_wrapper .dataTables_filter input:focus{outline:none;border-color:var(--red)}
  table.dataTable{width:100% !important;border-collapse:collapse !important;background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden}
  table.dataTable thead th{
    background:rgba(255,255,255,.03);color:var(--text-mute) !important;border-bottom:1px solid var(--line) !important;
    text-transform:uppercase;font-size:10px;letter-spacing:.14em;font-family:'Archivo',sans-serif;font-weight:700;padding:13px 16px;text-align:left
  }
  table.dataTable thead th.c{text-align:center}
  table.dataTable thead th.sorting:after,table.dataTable thead th.sorting_asc:after,table.dataTable thead th.sorting_desc:after{opacity:.4}
  table.dataTable tbody td{color:#fff;border-bottom:1px solid var(--line);padding:13px 16px;font-size:14px}
  table.dataTable tbody td.c{text-align:center}
  table.dataTable tbody td.nm{font-weight:600}
  table.dataTable tbody td.gd{color:var(--text-soft);font-size:13px}
  table.dataTable tbody tr{background:transparent !important}
  table.dataTable tbody tr:hover{background:rgba(255,255,255,.025) !important}
  table.dataTable.no-footer{border-bottom:1px solid var(--line)}
  .dataTables_wrapper .dataTables_info{color:var(--text-mute);font-size:12px;padding-top:12px}
  .dataTables_wrapper .dataTables_paginate{padding-top:10px}
  .dataTables_wrapper .dataTables_paginate .paginate_button{
    color:var(--text-soft) !important;background:transparent !important;border:1px solid var(--line) !important;
    border-radius:8px;margin:0 2px;padding:6px 12px !important
  }
  .dataTables_wrapper .dataTables_paginate .paginate_button:hover{background:rgba(255,255,255,.06) !important;color:#fff !important;border-color:rgba(255,255,255,.3) !important}
  .dataTables_wrapper .dataTables_paginate .paginate_button.current{background:var(--red) !important;border-color:var(--red) !important;color:#fff !important}
  .dataTables_wrapper .dataTables_paginate .paginate_button.disabled{opacity:.4}
  .dataTables_empty{color:var(--text-mute) !important;font-style:italic;padding:24px !important}
</style>
@endverbatim
@endpush

@section('content')
<section class="block" style="padding-top:48px">
  <div class="wrap" style="max-width:880px">
    <div class="block-head">
      <div class="eye">Registered Runners</div>
      <h2>Entry List</h2>
      <p>Cari peserta berdasarkan nama atau filter per kategori. Centang hijau berarti pembayaran lunas.</p>
    </div>

    <div class="el-toolbar">
      <label for="cat-filter">Kategori</label>
      <select id="cat-filter">
        <option value="">Semua Kategori</option>
        @foreach($categories as $cat)
          <option value="{{ $cat->distance }}">{{ $cat->distance }} — {{ $cat->name }}</option>
        @endforeach
      </select>
    </div>

    <table id="entry-table" class="display" style="width:100%">
      <thead>
        <tr>
          <th>Nama</th>
          <th>Kategori</th>
          <th>Gender</th>
          <th class="c">Bayar</th>
        </tr>
      </thead>
      <tbody>
        @foreach($categories as $cat)
          @foreach($cat->registrations as $reg)
            <tr>
              <td class="nm">{{ $reg->full_name }}</td>
              <td><span class="el-cat-tag">{{ $cat->distance }}</span></td>
              <td class="gd">{{ $reg->gender }}</td>
              <td class="c" data-order="{{ $reg->payment_status === 'paid' ? 1 : 0 }}">
                @if($reg->payment_status === 'paid')
                  <span class="el-pay-ok" title="Sudah bayar">✓</span>
                @else
                  <span class="el-pay-no" title="Belum bayar">—</span>
                @endif
              </td>
            </tr>
          @endforeach
        @endforeach
      </tbody>
    </table>

    <div class="el-legend">
      <span><span class="el-pay-ok" style="width:18px;height:18px;font-size:11px">✓</span> Sudah Bayar</span>
      <span><span class="el-pay-no" style="width:18px;height:18px;font-size:11px">—</span> Belum Bayar</span>
    </div>
  </div>
</section>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
@verbatim
<script>
  jQuery(function($){
    const table = $('#entry-table').DataTable({
      pageLength: 25,
      order: [[0, 'asc']],
      language: {
        search: '',
        searchPlaceholder: 'Cari nama peserta...',
        lengthMenu: 'Tampil _MENU_',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ peserta',
        infoEmpty: 'Tidak ada peserta',
        infoFiltered: '(difilter dari _MAX_)',
        zeroRecords: 'Peserta tidak ditemukan',
        paginate: { previous: '‹', next: '›' }
      }
    });

    $('#cat-filter').on('change', function(){
      const v = this.value;
      table.column(1).search(v ? '^' + v.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$' : '', true, false).draw();
    });
  });
</script>
@endverbatim
@endpush
@endsection
