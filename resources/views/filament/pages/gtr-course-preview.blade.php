<x-filament-panels::page>
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;margin-bottom:4px">
        @foreach($this->categories() as $c)
            <x-filament::button size="sm" :color="(string) $c->id === (string) $category ? 'primary' : 'gray'" wire:click="$set('category', '{{ $c->id }}')">
                {{ $c->distance }} · {{ $c->name }}
            </x-filament::button>
        @endforeach
        @if($url = $this->previewUrl())
            <x-filament::button size="sm" color="gray" tag="a" :href="$url" target="_blank" icon="heroicon-o-arrow-top-right-on-square">Buka layar penuh</x-filament::button>
        @endif
    </div>
    <p style="font-size:13px;opacity:.7;margin-bottom:8px">
        Tampilan peta sama persis dengan halaman detail kategori. Gunakan panel hitam di atas peta untuk export gambar resolusi tinggi (PNG).
        Perubahan pos / info jalur terlihat di sini setelah kategori disimpan.
    </p>
    @if($url)
        <iframe wire:key="pv-{{ $category }}" src="{{ $url }}" style="width:100%;height:calc(100vh - 220px);min-height:720px;border:0;border-radius:14px;background:#EEF0F3"></iframe>
    @else
        <div style="padding:20px;border-radius:12px;background:rgba(245,158,11,.1);color:#b45309">Belum ada kategori.</div>
    @endif
</x-filament-panels::page>
