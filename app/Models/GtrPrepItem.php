<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu item checklist persiapan panitia (perlengkapan / tugas), dicentang dari GTR Crew.
 */
class GtrPrepItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'done_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /** Fase persiapan (urut waktu). */
    public const PHASES = [
        'h7' => 'H-7',
        'h3' => 'H-3',
        'h1' => 'H-1',
        'race' => 'Hari-H',
        'post' => 'Setelah race',
    ];

    /**
     * Template checklist trail run: [kategori => [[judul, qty, fase, lokasi?], ...]].
     * Tombol "Isi template" menambahkan item yang belum ada (kategori + judul), jadi aman diulang
     * untuk memasukkan item template baru ke checklist yang sudah berjalan.
     */
    public const TEMPLATE = [
        'Rute & Marka' => [
            ['Survei final rute & cek titik rawan', null, 'h3'],
            ['Pasang marka rute (pita / flag)', null, 'h1'],
            ['Papan penunjuk arah & papan KM', null, 'h1'],
            ['Papan nama pos (START, CP, WS, FINISH)', '1 per pos', 'h1'],
            ['Marka & pita cadangan untuk sweeper', null, 'race'],
            ['Sweeper siap (min. 2 orang) + jalur sweep', '2 orang', 'race'],
        ],
        'Pos & Water Station' => [
            ['Tenda / terpal per pos', '1 per pos', 'h1'],
            ['Meja & kursi per pos', null, 'h1'],
            ['Air mineral (galon / botol)', null, 'h1', 'Semua WS'],
            ['Gelas / cup', null, 'h1', 'Semua WS'],
            ['Buah / gula merah / snack pelari', null, 'race', 'Semua WS'],
            ['Tempat & kantong sampah', '1 per pos', 'h1'],
        ],
        'Timing & Aplikasi' => [
            ['HP marshal terpasang GTR Crew & sudah login', '1 per pos', 'h3'],
            ['Power bank per pos', '1 per pos', 'h1'],
            ['Gladi scan di lokasi (cek sinyal tiap pos)', null, 'h3'],
            ['Reset data uji setelah gladi', null, 'h1'],
            ['Air horn / pistol start', '1', 'race', 'START'],
            ['Cek "Kesiapan sistem" di menu Kontrol', null, 'race'],
        ],
        'Surat & Undangan' => [
            ['Surat izin keramaian ke Polres / Polsek', null, 'h7'],
            ['Surat pemberitahuan kegiatan ke Pemkab Lombok Barat', null, 'h7'],
            ['Undangan Bupati Lombok Barat (pembukaan / flag off)', null, 'h7'],
            ['Undangan Camat Gerung', null, 'h7'],
            ['Undangan Kapolsek & Danramil Gerung', null, 'h7'],
            ['Undangan Kepala Desa / Lurah sekitar rute', null, 'h7'],
            ['Undangan Dinas Pariwisata & Dispora', null, 'h7'],
            ['Undangan sponsor & mitra', null, 'h7'],
            ['Undangan komunitas lari', null, 'h7'],
            ['Surat permohonan tim medis / ambulans (Puskesmas / Dinkes)', null, 'h7'],
            ['Surat pemberitahuan ke warga sekitar rute', null, 'h7'],
            ['Konfirmasi kehadiran tamu undangan & susunan protokol', null, 'h1'],
        ],
        'Racepack' => [
            ['BIB tercetak + peniti', null, 'h7'],
            ['Jersey per ukuran (cek jumlah)', null, 'h7'],
            ['Goodie bag & produk sponsor', null, 'h3'],
            ['Meja, banner & antrean pengambilan', null, 'h3'],
            ['Daftar peserta cetak (cadangan offline)', '1 set', 'h3'],
        ],
        'Medis & Keamanan' => [
            ['Ambulans standby', '1', 'race', 'START / FINISH'],
            ['Tim medis / P3K di pos', null, 'race'],
            ['Kotak P3K per pos', '1 per pos', 'h1'],
            ['Tandu', null, 'race'],
            ['Koordinasi polisi / linmas / desa', null, 'h7'],
            ['Grup WA darurat + nomor semua pos', null, 'h3'],
            ['HT / radio komunikasi', null, 'h1'],
        ],
        'Start & Finish' => [
            ['Gapura start / finish', null, 'h1'],
            ['Sound system & MC', null, 'race'],
            ['Pita finish', null, 'race', 'FINISH'],
            ['Medali finisher', null, 'h3', 'FINISH'],
            ['Podium & trofi', null, 'h1'],
            ['Banner sponsor terpasang', null, 'h1'],
        ],
        'Logistik & Konsumsi' => [
            ['Konsumsi panitia', null, 'race'],
            ['Konsumsi / refreshment finisher', null, 'race', 'FINISH'],
            ['Transport logistik ke pos', null, 'h1'],
            ['Genset / listrik', null, 'race', 'START / FINISH'],
        ],
        'Dokumentasi & Lainnya' => [
            ['Fotografer per titik', null, 'race'],
            ['Briefing seluruh panitia', null, 'h1'],
            ['Bersih-bersih rute & pos (sweep sampah)', null, 'post'],
            ['Kembalikan perlengkapan pinjaman', null, 'post'],
        ],
    ];

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /**
     * Tambahkan item TEMPLATE yang belum ada (dicocokkan dari kategori + judul). Item yang sudah
     * ada — termasuk centang, PIC, catatan — tidak disentuh. @return int jumlah item dibuat
     */
    public static function fillTemplate(): int
    {
        $existing = static::query()->get(['category', 'title'])
            ->map(fn ($i) => mb_strtolower($i->category.'|'.$i->title))->flip();
        $sort = (int) static::max('sort_order');
        $n = 0;
        foreach (self::TEMPLATE as $category => $rows) {
            foreach ($rows as $row) {
                if ($existing->has(mb_strtolower($category.'|'.$row[0]))) {
                    continue;
                }
                static::create([
                    'category' => $category,
                    'title' => $row[0],
                    'qty' => $row[1] ?? null,
                    'phase' => $row[2] ?? null,
                    'location' => $row[3] ?? null,
                    'sort_order' => $sort += 10,
                ]);
                $n++;
            }
        }

        return $n;
    }
}
