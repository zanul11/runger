<x-filament-panels::page>
    @php($r = $this->getReport())
    @php($idr = fn ($n) => 'IDR ' . number_format((int) $n, 0, ',', '.'))

    {{-- Ringkasan --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="text-sm text-gray-500 dark:text-gray-400">Pendaftar Lunas</div>
            <div class="mt-1 text-3xl font-bold text-green-600">{{ number_format($r['count'], 0, ',', '.') }}</div>
            <div class="mt-1 text-xs text-gray-400">{{ $r['pending'] }} menunggu pembayaran</div>
        </div>
        <div class="fi-section rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-gray-900 sm:col-span-2">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Uang Masuk</div>
            <div class="mt-1 text-3xl font-bold text-primary-600">{{ $idr($r['total']) }}</div>
            <div class="mt-1 text-xs text-gray-400">Dari {{ number_format($r['count'], 0, ',', '.') }} pembayaran lunas</div>
        </div>
    </div>

    {{-- Rincian per metode --}}
    <div class="fi-section overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div class="border-b border-gray-200 px-5 py-3 text-sm font-semibold dark:border-white/10">Per Metode Pembayaran</div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-400">
                    <th class="px-5 py-3">Metode</th>
                    <th class="px-5 py-3 text-right">Jumlah Pendaftar</th>
                    <th class="px-5 py-3 text-right">Total Uang</th>
                </tr>
            </thead>
            <tbody>
                @forelse($r['by_method'] as $row)
                    <tr class="border-t border-gray-100 dark:border-white/5">
                        <td class="px-5 py-3 font-medium">{{ $row['method'] }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ number_format($row['count'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-semibold tabular-nums">{{ $idr($row['total']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-6 text-center text-gray-400">Belum ada pembayaran lunas.</td></tr>
                @endforelse
            </tbody>
            @if(count($r['by_method']))
                <tfoot>
                    <tr class="border-t-2 border-gray-200 font-bold dark:border-white/10">
                        <td class="px-5 py-3">Total</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ number_format($r['count'], 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right tabular-nums">{{ $idr($r['total']) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-filament-panels::page>
