<x-filament-panels::page>
    @php $total = collect($categories)->sum('paid') - collect($categories)->sum('no_bib'); @endphp
    <p style="font-size:13px;opacity:.75;margin:0">
        BIB dibuat dari data peserta <b>lunas</b> yang sudah punya nomor BIB: nomor, nama, gender, ukuran jersey, golongan darah,
        kontak keluarga, SOS panitia (Header GTR → <i>Kontak Darurat Panitia</i>), sponsor aktif, dan profil elevasi kategori.
        Ukuran <b>A5 landscape (21 × 14,8 cm)</b>, satu BIB per halaman — cetak 100% / <i>actual size</i>.
    </p>
    <label style="display:flex;align-items:center;gap:8px;font-size:13px">
        <input type="checkbox" wire:model.live="flip" style="width:16px;height:16px">
        Profil elevasi dicetak <b>terbalik 180°</b> (terbaca oleh pelari saat menunduk)
    </label>
    @php $f = $flip ? 1 : 0; @endphp

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
        @foreach($categories as $c)
            <x-filament::section>
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                    <span style="width:12px;height:12px;border-radius:50%;background:{{ $c['color'] }}"></span>
                    <b style="font-size:16px">{{ $c['label'] }}</b>
                </div>
                <div style="font-size:13px;line-height:1.7">
                    {{ $c['paid'] - $c['no_bib'] }} BIB siap cetak
                    @if($c['no_bib'])<br><span style="color:#d97706">{{ $c['no_bib'] }} peserta lunas belum punya BIB</span>@endif
                    @unless($c['has_ele'])<br><span style="color:#d97706">Profil elevasi kosong — unggah GPX di menu Kategori GTR</span>@endunless
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                    <x-filament::button size="sm" tag="a" target="_blank" :href="route('gtr.bib.pdf', ['category' => $c['id'], 'flip' => $f])" icon="heroicon-o-printer" :disabled="$c['paid'] - $c['no_bib'] < 1">Unduh PDF</x-filament::button>
                    <x-filament::button size="sm" color="gray" tag="a" target="_blank" :href="route('gtr.bib.pdf', ['category' => $c['id'], 'sample' => 1, 'flip' => $f])" icon="heroicon-o-eye" :disabled="$c['paid'] - $c['no_bib'] < 1">Contoh 1 BIB</x-filament::button>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;align-items:end;gap:12px;justify-content:space-between">
            <form method="GET" action="{{ route('gtr.bib.pdf') }}" target="_blank" style="display:flex;align-items:end;gap:8px">
                <label style="font-size:13px">
                    <span style="display:block;margin-bottom:4px;font-weight:600">Cetak satu BIB (cetak ulang / ganti)</span>
                    <x-filament::input.wrapper><x-filament::input type="text" name="bib" placeholder="Nomor BIB, mis. 5001" required /></x-filament::input.wrapper>
                </label>
                <input type="hidden" name="flip" value="{{ $f }}">
                <x-filament::button type="submit" size="sm" color="gray" icon="heroicon-o-printer">Cetak</x-filament::button>
            </form>
            <x-filament::button tag="a" target="_blank" :href="route('gtr.bib.pdf', ['category' => 'all', 'flip' => $f])" icon="heroicon-o-arrow-down-tray" :disabled="$total < 1">
                Unduh semua BIB ({{ $total }})
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
