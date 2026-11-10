<x-filament-panels::page>
    @php
        $wita = fn ($t) => $t ? $t->timezone('Asia/Makassar')->format('H:i:s') : '—';
        $matrix = $this->getMatrix();
        $devices = $this->getDevices();
        $sweep = $this->getSweep();
        $th = 'text-align:left;padding:8px 10px;font-size:12px;opacity:.7;font-weight:600;border-bottom:1px solid rgba(127,127,127,.25)';
        $td = 'padding:8px 10px;border-bottom:1px solid rgba(127,127,127,.15);font-size:14px';
    @endphp

    <div wire:poll.15s style="display:grid;gap:20px">
        <p style="font-size:13px;opacity:.75;margin:0">Diperbarui otomatis tiap 15 detik · {{ now()->format('H:i:s') }} WITA. Hanya scan sah yang dihitung (duplikat/dibatalkan tidak).</p>

        {{-- 1. Peserta per pos per kategori --}}
        <x-filament::section heading="Peserta tercatat per pos" description="Jumlah peserta unik yang sudah discan / total peserta kategori. Kosong = kategori tidak lewat pos itu.">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead><tr>
                        <th style="{{ $th }}">Pos</th>
                        @foreach ($matrix['categories'] as $c)<th style="{{ $th }};text-align:center">{{ $c->name }}</th>@endforeach
                        <th style="{{ $th }}">Marshal aktif</th>
                        <th style="{{ $th }}">Scan terakhir</th>
                    </tr></thead>
                    <tbody>
                    @foreach ($matrix['rows'] as $row)
                        <tr>
                            <td style="{{ $td }}"><b>{{ $row['code'] }}</b> <span style="opacity:.7">{{ $row['name'] }}</span></td>
                            @foreach ($matrix['categories'] as $c)
                                @php $cell = $row['cells'][$c->id]; @endphp
                                <td style="{{ $td }};text-align:center;font-variant-numeric:tabular-nums">
                                    @if ($cell)
                                        <b style="font-size:18px">{{ $cell['seen'] }}</b><span style="opacity:.6"> / {{ $cell['total'] }}</span>
                                        @if ($cell['total'])
                                            <div style="height:4px;margin-top:4px;border-radius:2px;background:rgba(127,127,127,.2)"><div style="height:4px;border-radius:2px;background:rgb(34,197,94);width:{{ min(100, round($cell['seen'] / $cell['total'] * 100)) }}%"></div></div>
                                        @endif
                                    @else
                                        <span style="opacity:.3">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td style="{{ $td }}">
                                @if (count($row['marshals'])){{ implode(', ', $row['marshals']) }}@else<span style="color:rgb(217,119,6)">belum ada</span>@endif
                            </td>
                            <td style="{{ $td }};font-variant-numeric:tabular-nums">{{ $wita($row['last_at']) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- 2. Device --}}
        <x-filament::section heading="Device scanner" description="Kuning: tidak ada data masuk ≥ {{ \App\Support\GtrRaceDayStats::QUIET_MIN }} menit. Merah: ≥ {{ \App\Support\GtrRaceDayStats::STALE_MIN }} menit — HP mungkin offline / mati; data tetap aman di HP dan terkirim saat online.">
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse">
                    <thead><tr>
                        <th style="{{ $th }}">Device</th><th style="{{ $th }}">Marshal</th><th style="{{ $th }}">Pos</th>
                        <th style="{{ $th }};text-align:right">Scan</th><th style="{{ $th }}">Data terakhir masuk</th><th style="{{ $th }}">Scan terakhir</th>
                    </tr></thead>
                    <tbody>
                    @forelse ($devices as $d)
                        @php
                            $color = ['ok' => 'rgb(22,163,74)', 'quiet' => 'rgb(217,119,6)', 'stale' => 'rgb(220,38,38)', 'idle' => 'rgb(107,114,128)'][$d['state']];
                            $label = ['ok' => 'aktif', 'quiet' => 'diam', 'stale' => 'offline?', 'idle' => 'belum kirim'][$d['state']];
                        @endphp
                        <tr>
                            <td style="{{ $td }};font-family:monospace">
                                <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:{{ $color }};margin-right:6px"></span>{{ substr($d['device_id'], 0, 8) }}
                                <span style="font-size:11px;color:{{ $color }};font-family:inherit">{{ $label }}</span>
                            </td>
                            <td style="{{ $td }}">{{ $d['user'] ?? '—' }}</td>
                            <td style="{{ $td }}">{{ $d['point'] }}</td>
                            <td style="{{ $td }};text-align:right;font-variant-numeric:tabular-nums">{{ $d['total'] }}</td>
                            <td style="{{ $td }}">{{ $wita($d['last_received']) }} <span style="opacity:.6;font-size:12px">{{ $d['last_received']?->diffForHumans() }}</span></td>
                            <td style="{{ $td }}">{{ $wita($d['last_scanned']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="{{ $td }};opacity:.6">Belum ada device yang login / mengirim scan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- 3. Sweeper --}}
        @foreach ($sweep as $s)
            <x-filament::section :heading="$s['category'] . ' — belum terlihat di CP terakhir' . ($s['last_cp'] ? ' (' . $s['last_cp'] . ')' : '')"
                :description="'Di lintasan: ' . $s['on_course'] . ' · Finish: ' . $s['finished'] . ' · Belum tercatat di pos mana pun: ' . $s['not_started'] . '. Urut dari yang paling lama tidak terlihat.'">
                @if (count($s['rows']))
                    <div style="overflow-x:auto">
                        <table style="width:100%;border-collapse:collapse">
                            <thead><tr>
                                <th style="{{ $th }}">BIB</th><th style="{{ $th }}">Nama</th><th style="{{ $th }}">Terakhir di</th>
                                <th style="{{ $th }}">Jam</th><th style="{{ $th }}">HP peserta</th><th style="{{ $th }}">Kontak darurat</th>
                            </tr></thead>
                            <tbody>
                            @foreach ($s['rows'] as $r)
                                <tr>
                                    <td style="{{ $td }}"><b>{{ $r['bib'] }}</b></td>
                                    <td style="{{ $td }}">{{ $r['name'] }} <span style="opacity:.6">{{ $r['gender'] === 'F' ? 'P' : 'L' }}</span></td>
                                    <td style="{{ $td }}">{{ $r['last_point'] }}</td>
                                    <td style="{{ $td }};font-variant-numeric:tabular-nums">{{ $wita($r['last_at']) }} <span style="opacity:.6;font-size:12px">{{ $r['last_at']->diffForHumans(null, true) }}</span></td>
                                    <td style="{{ $td }}">{{ $r['phone'] ?: '—' }}</td>
                                    <td style="{{ $td }}">{{ $r['emergency'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="opacity:.6;margin:0">Tidak ada peserta tertinggal sebelum CP terakhir.</p>
                @endif
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
