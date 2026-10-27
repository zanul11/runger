<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px">
        <p style="font-size:13px;opacity:.7;margin:0">
            Slide dibuat otomatis dari konten admin GTR (Header GTR, Kategori, Rules, Sponsor). Centang "Edit teks" untuk mengubah teks langsung di slide.
            Kalau sudah final, klik <b>Publikasikan ke website</b> agar muncul di menu Race Guide halaman Gerung Trail Run.
        </p>
        <x-filament::button size="sm" color="gray" tag="a" :href="route('gtr.race-guide')" target="_blank" icon="heroicon-o-arrow-top-right-on-square">Buka layar penuh</x-filament::button>
    </div>
    <iframe src="{{ route('gtr.race-guide') }}" style="width:100%;height:calc(100vh - 200px);min-height:760px;border:0;border-radius:14px;background:#EEF0F3"></iframe>
</x-filament-panels::page>
