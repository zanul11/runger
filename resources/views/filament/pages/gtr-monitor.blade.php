<x-filament-panels::page>
    <div wire:poll.15s class="space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Diperbarui otomatis tiap 15 detik. Menampilkan marshal aktif, jumlah scan, dan scan terakhir per pos.
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($this->getRows() as $row)
                <div class="fi-section rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-lg font-bold tracking-wide">{{ $row['code'] }}</span>
                            <span @class([
                                'ml-2 rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-green-100 text-green-700' => $row['type'] === 'start',
                                'bg-red-100 text-red-700' => $row['type'] === 'finish',
                                'bg-blue-100 text-blue-700' => $row['type'] === 'water_station',
                                'bg-gray-100 text-gray-700' => $row['type'] === 'checkpoint',
                            ])>{{ $row['type'] }}</span>
                        </div>
                        <span class="text-2xl font-extrabold tabular-nums">{{ number_format($row['scan_count']) }}</span>
                    </div>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $row['name'] }}</p>

                    <div class="mt-3 space-y-1 text-sm">
                        <div>
                            <span class="text-gray-400">Marshal:</span>
                            @if (count($row['marshals']))
                                <span class="font-medium">{{ implode(', ', $row['marshals']) }}</span>
                            @else
                                <span class="text-amber-600">— belum ada marshal aktif</span>
                            @endif
                        </div>
                        <div>
                            <span class="text-gray-400">Scan terakhir:</span>
                            <span class="font-medium">
                                {{ $row['last_scan'] ? $row['last_scan']->timezone('Asia/Makassar')->format('d M H:i:s') . ' WITA' : '—' }}
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
