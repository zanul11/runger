<x-filament-panels::page>
    @php
        $cats = $this->getCategories();
        $fired = $cats->firstWhere('gun_fired_at', '!=', null);
        $schedule = $cats->pluck('gun_start')->filter()->unique(fn ($t) => $t->format('Y-m-d H:i'));
    @endphp

    <p style="font-size:13px;opacity:.75;margin:0">
        Semua kategori start <b>bersama</b> dan <b>tidak ada scan di garis start</b>: gun time ini dipakai semua peserta
        (dasar waktu & ranking). Tekan <b>START</b> tepat saat pistol ditembak. Bila telat ditekan, pakai <b>Koreksi gun time</b>.
    </p>

    <div wire:poll.10s>
        <x-filament::section>
            <div style="display:flex;flex-wrap:wrap;gap:24px;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-size:13px;opacity:.7">Kategori</div>
                    <div style="font-size:26px;font-weight:800">{{ $cats->pluck('name')->join(' + ') ?: '—' }}</div>
                    <div style="font-size:13px;opacity:.7;margin-top:4px">
                        {{ $cats->sum('eligible_count') }} peserta ·
                        Jadwal: {{ $schedule->count() === 1 ? $schedule->first()->format('d M H:i') . ' WITA' : ($schedule->isEmpty() ? ($cats->first()?->start_time ?: '—') : 'BERBEDA per kategori — samakan di Kategori GTR') }}
                    </div>
                </div>

                @if ($fired)
                    <div style="padding:12px 16px;border-radius:12px;background:rgba(34,197,94,.12)">
                        <div style="font-size:12px;letter-spacing:.08em;color:rgb(22,163,74);font-weight:700">SUDAH START</div>
                        <div style="font-size:34px;font-weight:800;font-variant-numeric:tabular-nums">{{ $fired->gun_fired_at->format('H:i:s.v') }}</div>
                        <div style="font-size:12px;opacity:.7">{{ $fired->gun_fired_at->format('d M Y') }} WITA · oleh {{ $fired->gunFiredBy?->name ?? '—' }}</div>
                    </div>
                @else
                    <div style="padding:12px 16px;border-radius:12px;background:rgba(234,179,8,.12);font-weight:800;font-size:18px;color:rgb(161,98,7)">BELUM START</div>
                @endif
            </div>

            <div style="margin-top:16px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                @unless ($fired){{ $this->startAllAction }}@endunless
                {{ $this->correctAllAction }}
            </div>
        </x-filament::section>

        @if ($fired && $cats->contains(fn ($c) => ! $c->gun_fired_at || ! $c->gun_fired_at->eq($fired->gun_fired_at)))
            <x-filament::section style="margin-top:16px">
                <p style="margin:0;color:rgb(217,119,6);font-weight:600">Gun time antar kategori berbeda. Pakai "Koreksi gun time" untuk menyamakan.</p>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
