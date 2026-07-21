<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Laporan Pembayaran — {{ $setting->title ?? 'Gerung Trail Run 2026' }}</title>
@php($idr = fn ($n) => 'IDR ' . number_format((int) $n, 0, ',', '.'))
<style>
  :root{ --ink:#111827; --muted:#6b7280; --line:#e5e7eb; --brand:#1B3FAE; --accent:#E53935; --soft:#f9fafb; }
  *{box-sizing:border-box}
  html,body{margin:0;padding:0;background:#eef1f6;color:var(--ink);font-family:'Segoe UI',Arial,sans-serif}
  .toolbar{max-width:210mm;margin:14px auto 0;text-align:right}
  .btn{background:var(--brand);color:#fff;border:none;border-radius:8px;padding:10px 20px;font-size:14px;font-weight:700;cursor:pointer}
  .sheet{width:210mm;min-height:297mm;margin:14px auto;background:#fff;padding:16mm 16mm 14mm;box-shadow:0 4px 22px rgba(0,0,0,.12)}

  .hd{display:flex;align-items:center;gap:16px;border-bottom:3px solid var(--brand);padding-bottom:14px}
  .hd img{height:56px;width:auto}
  .hd .t{flex:1}
  .hd .kicker{font-size:10px;letter-spacing:.22em;text-transform:uppercase;color:var(--accent);font-weight:800}
  .hd h1{margin:3px 0 2px;font-size:22px;letter-spacing:.01em}
  .hd .sub{font-size:11.5px;color:var(--muted);line-height:1.5}
  .hd .meta{text-align:right;font-size:10.5px;color:var(--muted);line-height:1.6}
  .hd .meta b{color:var(--ink)}

  /* Kartu ringkasan */
  .cards{display:flex;gap:12px;margin:20px 0 6px}
  .card{flex:1;border:1px solid var(--line);border-radius:12px;padding:14px 16px;background:var(--soft)}
  .card .lab{font-size:9.5px;letter-spacing:.12em;text-transform:uppercase;color:var(--muted);font-weight:700}
  .card .val{font-size:24px;font-weight:800;margin-top:6px}
  .card.big{flex:1.6;background:linear-gradient(135deg,#1B3FAE,#3457c9);border:none;color:#fff}
  .card.big .lab{color:rgba(255,255,255,.8)}
  .card .val.green{color:#059669}
  .card .val.amber{color:#d97706}
  .card .val.small{font-size:16px;color:var(--muted);font-weight:700}

  h2.sec{font-size:12px;letter-spacing:.14em;text-transform:uppercase;color:var(--brand);margin:26px 0 10px;padding-bottom:6px;border-bottom:1px solid var(--line)}
  .cols{display:flex;gap:22px}
  .col{flex:1}
  table{width:100%;border-collapse:collapse;font-size:12.5px}
  thead th{text-align:left;font-size:9.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);border-bottom:1.5px solid var(--ink);padding:8px 8px}
  thead th.r,tbody td.r,tfoot td.r{text-align:right}
  tbody td{padding:9px 8px;border-bottom:1px solid var(--line)}
  tbody td.nm{font-weight:600}
  tbody td.r{font-variant-numeric:tabular-nums}
  tfoot td{padding:9px 8px;border-top:2px solid var(--ink);font-weight:800;font-variant-numeric:tabular-nums}
  .empty{color:var(--muted);font-style:italic;text-align:center;padding:16px}

  .sign{display:flex;justify-content:flex-end;margin-top:40px}
  .sign .b{width:64mm;text-align:center;font-size:11.5px;color:var(--muted)}
  .sign .b .place{margin-bottom:56px}
  .sign .b .ln{border-top:1px solid var(--ink);padding-top:5px}
  .foot{margin-top:20px;font-size:9px;color:#9ca3af;text-align:center;border-top:1px solid var(--line);padding-top:8px}

  @media print{
    html,body{background:#fff}
    .toolbar{display:none}
    .sheet{margin:0;box-shadow:none;width:auto;min-height:auto;padding:10mm}
    @page{size:A4;margin:10mm}
  }
</style>
</head>
<body>
  <div class="toolbar"><button class="btn" onclick="window.print()">🖨️ Cetak / Simpan PDF</button></div>

  <div class="sheet">
    <div class="hd">
      <img src="{{ asset('assets/gtr/logo.jpeg') }}" alt="GTR">
      <div class="t">
        <div class="kicker">Laporan Keuangan</div>
        <h1>Laporan Pembayaran</h1>
        <div class="sub">{{ $setting->title ?? 'Gerung Trail Run 2026' }} · {{ $setting->location_text ?? 'Bukit Keteri, Gerung — Lombok Barat' }}</div>
      </div>
      <div class="meta">
        <div>Dicetak</div>
        <b>{{ now()->timezone('Asia/Makassar')->translatedFormat('d F Y · H:i') }} WITA</b>
      </div>
    </div>

    {{-- Ringkasan --}}
    <div class="cards">
      <div class="card"><div class="lab">Pendaftar Lunas</div><div class="val green">{{ number_format($report['count'], 0, ',', '.') }}</div></div>
      <div class="card big"><div class="lab">Total Uang Masuk</div><div class="val">{{ $idr($report['total']) }}</div></div>
      <div class="card"><div class="lab">Menunggu / Batal</div><div class="val amber">{{ $report['pending'] }} <span class="small">/ {{ $report['cancelled'] }}</span></div></div>
    </div>

    <div class="cols">
      {{-- Per metode --}}
      <div class="col">
        <h2 class="sec">Per Metode Pembayaran</h2>
        <table>
          <thead><tr><th>Metode</th><th class="r">Pendaftar</th><th class="r">Total</th></tr></thead>
          <tbody>
            @forelse($report['by_method'] as $row)
              <tr><td class="nm">{{ $row['method'] }}</td><td class="r">{{ number_format($row['count'], 0, ',', '.') }}</td><td class="r">{{ $idr($row['total']) }}</td></tr>
            @empty
              <tr><td colspan="3" class="empty">Belum ada pembayaran.</td></tr>
            @endforelse
          </tbody>
          @if(count($report['by_method']))
            <tfoot><tr><td>Total</td><td class="r">{{ number_format($report['count'], 0, ',', '.') }}</td><td class="r">{{ $idr($report['total']) }}</td></tr></tfoot>
          @endif
        </table>
      </div>

      {{-- Per kategori --}}
      <div class="col">
        <h2 class="sec">Per Kategori</h2>
        <table>
          <thead><tr><th>Kategori</th><th class="r">Pendaftar</th><th class="r">Total</th></tr></thead>
          <tbody>
            @forelse($report['by_category'] as $row)
              <tr><td class="nm">{{ $row['category'] }}</td><td class="r">{{ number_format($row['count'], 0, ',', '.') }}</td><td class="r">{{ $idr($row['total']) }}</td></tr>
            @empty
              <tr><td colspan="3" class="empty">Belum ada pembayaran.</td></tr>
            @endforelse
          </tbody>
          @if(count($report['by_category']))
            <tfoot><tr><td>Total</td><td class="r">{{ number_format($report['count'], 0, ',', '.') }}</td><td class="r">{{ $idr($report['total']) }}</td></tr></tfoot>
          @endif
        </table>
      </div>
    </div>

    <div class="sign">
      <div class="b"><div class="place">Gerung, {{ now()->timezone('Asia/Makassar')->translatedFormat('d F Y') }}</div><div class="ln">Bendahara / Panitia</div></div>
    </div>

    <div class="foot">Dokumen ini dihasilkan otomatis oleh sistem pendaftaran {{ $setting->title ?? 'Gerung Trail Run 2026' }}.</div>
  </div>

  <script>
    if (new URLSearchParams(location.search).get('print') === '1') {
      window.addEventListener('load', () => setTimeout(() => window.print(), 400))
    }
  </script>
</body>
</html>
