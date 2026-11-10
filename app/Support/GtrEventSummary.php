<?php

namespace App\Support;

use App\Models\GtrCategory;
use App\Models\GtrRegistration;
use App\Models\GtrSponsor;
use Illuminate\Support\Carbon;

/**
 * Ringkasan data GTR untuk presentasi & laporan (sosialisasi desa, sponsor, pemda).
 * Hanya angka gabungan — tidak ada nama, kontak, atau data pribadi peserta.
 */
class GtrEventSummary
{
    /**
     * Asal daerah dari isian alamat (teks bebas) → kabupaten/kota. Dicek berurutan; kata kunci
     * berupa nama kabupaten, kecamatan, desa/kelurahan yang umum ditulis peserta.
     */
    private const REGIONS = [
        'Luar negeri' => 'australia|malaysia|singapore|singapura|jepang|japan|korea|belanda|netherlands|united states|amerika|jerman|germany|prancis|perancis|france|inggris|england|london',
        'Pulau Jawa' => 'jawa|jakarta|surabaya|jemursari|bandung|yogya|jogja|malang|semarang|tangerang|bekasi|depok|bogor|\bsolo\b|surakarta|banten|sidoarjo|cirebon',
        'Luar NTB lainnya' => 'makassar|sulawesi|kalimantan|sumatera|sumatra|papua|kupang|\bntt\b|flores|maluku',
        'Bali' => 'bali|buleleng|denpasar|badung|gianyar|tabanan|karangasem|singaraja|klungkung|bangli|jembrana',
        'Sumbawa Barat' => 'sumbawa barat|\bksb\b|taliwang|maluk|sekongkang|jereweh',
        'Sumbawa' => 'sumbawa',
        'Dompu' => 'dompu',
        'Bima' => '\bbima\b',
        'Lombok Utara' => 'lombok utara|\bklu\b|pemenang|tanjung(?! karang)|gangga|kayangan|bayan',
        'Lombok Timur' => 'lombok timur|lotim|selong|masbagik|aikmel|jerowaru|terara|sakra|sikur|labuhan haji|pringgabaya|sambelia|suralaga|keruak|montong gading|wanasaba|sukamulia|suela|pringgasela|sembalun|bebidas|rarang',
        'Lombok Tengah' => 'lombok tengah|loteng|praya|kopang|jonggat|puyung|batukliang|pringgarata|janapria|sengkol|pujut|kuta lombok|kuta mandalika|mujur|praya barat|praya timur|selebung',
        'Lombok Barat' => 'lombok barat|lobar|gerung|banyu urip|dasan tapen|kebon ayu|beleke|giri tembesi|dasan geres|tempos|mesanggok|gapuk|babussalam|taman ayu|kediri|kuripan|jagaraga|labuapi|labu api|pengsong|lelede|sandik|medas|bagik polak|lembar|sekotong|narmada|suranadi|sedau|lingsar|duman|gunungsari|sesela|kekait|batulayar|senggigi|meninting|perampuan|merembu|telaga waru|batu kumbung',
        'Kota Mataram' => 'mataram|ampenan|sekarbela|selaparang|cakranegara|cakra\b|sandubaya|jempong|pagesangan|monjok|kekalik|karang baru|dasan cermen|pejanggik|gomong|rembiga|karang pule|tanjung karang|dasan agung|punia|pagutan|babakan|turida|bertais|sweta|karang taliwang|kebon roek|majeluk|pejeruk|dayan peken|mayura|pamotan|udayana|selagalas|rembige|kekalek|pejarakan|batu ringgit|sayang.sayang|karang kelok|gegutu',
    ];

    private const GERUNG = 'gerung|banyu urip|dasan tapen|kebon ayu|beleke|giri tembesi|dasan geres|tempos|mesanggok|gapuk|babussalam|taman ayu';

    public static function region(?string $address): string
    {
        $a = mb_strtolower((string) $address);
        foreach (self::REGIONS as $name => $re) {
            if (preg_match('/' . $re . '/u', $a)) {
                return $name;
            }
        }

        return 'Lainnya';
    }

    public static function build(): array
    {
        $paid = GtrRegistration::with('category')->where('payment_status', 'paid')->get();
        $raceDay = Carbon::parse(config('services.gtr.race_date', '2026-11-29'));

        $regions = $paid->countBy(fn ($r) => self::region($r->address))->sortDesc();
        $gerung = $paid->filter(fn ($r) => preg_match('/' . self::GERUNG . '/u', mb_strtolower((string) $r->address)))->count();
        $lobar = (int) ($regions['Lombok Barat'] ?? 0);
        $known = $paid->count() - (int) ($regions['Lainnya'] ?? 0);

        $ages = $paid->filter(fn ($r) => $r->birth_date)->map(fn ($r) => (int) floor(Carbon::parse($r->birth_date)->diffInYears($raceDay)))->filter(fn ($a) => $a > 5 && $a < 100);
        $ageGroups = collect(['< 20' => [0, 19], '20–29' => [20, 29], '30–39' => [30, 39], '40–49' => [40, 49], '50+' => [50, 200]])
            ->map(fn ($r) => $ages->filter(fn ($a) => $a >= $r[0] && $a <= $r[1])->count());

        $clubs = $paid->map(fn ($r) => mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $r->club))))
            ->filter(fn ($c) => $c !== '' && ! in_array($c, ['-', '--', 'tidak ada', 'tidak', 'belum ada', 'none', 'no', 'umum', 'pribadi', 'individu', 'perorangan'], true))
            ->unique()->count();

        return [
            'generated_at' => now('Asia/Makassar')->format('Y-m-d H:i') . ' WITA',
            'paid' => $paid->count(),
            'pending' => GtrRegistration::where('payment_status', 'pending')->count(),
            'categories' => GtrCategory::where('is_active', true)->orderBy('sort_order')->get()
                ->mapWithKeys(fn ($c) => [trim(($c->distance ?: $c->name) . ($c->tag ? ' ' . $c->tag : '')) => $paid->where('gtr_category_id', $c->id)->count()])->all(),
            'gender' => $paid->countBy(fn ($r) => $r->gender ?: 'Tidak diisi')->all(),
            'regions' => $regions->all(),
            'regions_count' => $regions->keys()->reject(fn ($k) => $k === 'Lainnya')->count(),
            'kec_gerung' => $gerung,
            'outside_lobar' => $known - $lobar,
            'outside_lobar_pct' => $known ? (int) round(($known - $lobar) / $known * 100) : 0,
            'age_groups' => $ageGroups->all(),
            'age_min' => $ages->min(),
            'age_max' => $ages->max(),
            'clubs' => $clubs,
            'sponsors' => GtrSponsor::where('is_active', true)->count(),
        ];
    }

    /** Teks ringkas siap salin (untuk presentasi / laporan). */
    public static function text(array $s): string
    {
        $line = fn (array $rows) => collect($rows)->map(fn ($v, $k) => "  - {$k}: {$v}")->implode("\n");

        return implode("\n", [
            "RINGKASAN GTR 2026 (per {$s['generated_at']})",
            "Peserta lunas: {$s['paid']} (menunggu pembayaran: {$s['pending']})",
            'Per kategori:', $line($s['categories']),
            'Gender:', $line($s['gender']),
            "Asal daerah ({$s['regions_count']} kabupaten/kota/wilayah terbaca):", $line($s['regions']),
            "Dari Kecamatan Gerung: {$s['kec_gerung']}",
            "Dari luar Lombok Barat: {$s['outside_lobar']} ({$s['outside_lobar_pct']}% dari alamat yang terbaca)",
            "Usia: {$s['age_min']}–{$s['age_max']} tahun", $line($s['age_groups']),
            "Komunitas / klub lari berbeda: {$s['clubs']}",
            "Sponsor aktif: {$s['sponsors']}",
        ]);
    }
}
