<x-filament-panels::page>
    <p style="font-size:13px;opacity:.75;margin:0">
        Angka gabungan peserta lunas untuk presentasi dan laporan (desa, sponsor, pemda) — tanpa nama atau kontak peserta.
        Asal daerah dibaca dari isian alamat, jadi alamat yang tidak jelas masuk <i>Lainnya</i>. Data per {{ $s['generated_at'] }}.
    </p>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
        @foreach([
            ['Peserta lunas', $s['paid']],
            ['Dari luar Lombok Barat', $s['outside_lobar'] . ' (' . $s['outside_lobar_pct'] . '%)'],
            ['Kabupaten/kota asal', $s['regions_count']],
            ['Dari Kec. Gerung', $s['kec_gerung']],
            ['Komunitas lari', $s['clubs']],
            ['Sponsor aktif', $s['sponsors']],
        ] as [$label, $value])
            <x-filament::section>
                <div style="font-size:12px;opacity:.7">{{ $label }}</div>
                <div style="font-size:26px;font-weight:700;margin-top:4px">{{ $value }}</div>
            </x-filament::section>
        @endforeach
    </div>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
        @foreach([
            'Asal daerah' => $s['regions'],
            'Kategori' => $s['categories'],
            'Kelompok usia' => $s['age_groups'],
            'Gender' => $s['gender'],
        ] as $title => $rows)
            <x-filament::section :heading="$title">
                <table style="width:100%;font-size:13px">
                    @foreach($rows as $k => $v)
                        <tr><td style="padding:3px 0">{{ $k }}</td><td style="text-align:right;font-weight:600">{{ $v }}</td></tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section heading="Teks ringkasan (salin untuk presentasi)">
        <textarea readonly onclick="this.select()" style="width:100%;min-height:300px;font-family:ui-monospace,monospace;font-size:12.5px;border-radius:8px;padding:12px;background:rgba(127,127,127,.08);border:1px solid rgba(127,127,127,.25)">{{ $text }}</textarea>
    </x-filament::section>
</x-filament-panels::page>
