<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\RosterConsumptionSetting;
use App\Models\RosterPosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data awal roster panitia hari H — Gerung Trail Run (GTR) 2026.
 *
 *   php artisan db:seed --class=RosterGtr2026Seeder
 *
 * Aman diulang: struktur hanya dibuat bila event belum punya area roster, dan nama event yang
 * sudah ada tidak diubah (hanya info roster: Race Village, rute, open gate, penyelenggara).
 */
class RosterGtr2026Seeder extends Seeder
{
    /** [area => [grup => [[nama, min, max, deskripsi, ikon, km?, catatan_tambahan?], ...]]] */
    private function structure(): array
    {
        $m = RosterPosition::DEFAULT_MARSHAL_DESC;

        return [
            'Race Village' => [
                'Komando' => [
                    ['Race Director', 1, 1, 'Pengambil keputusan: start, cut-off, kondisi darurat.', 'compass'],
                    ['Race Control & Komunikasi', 1, 2, 'Monitor HT/WA grup, rekap laporan pos, data DNF/DNS.', 'radio'],
                    ['Timing & Result', 2, 3, 'Scan finish, admin sistem timing, publish hasil.', 'watch'],
                ],
                'Start / Finish' => [
                    ['Start/Finish & Chute', 3, 4, 'Atur corral start per kategori, jaga jalur finish.', 'flag'],
                    ['Medali & Finisher', 2, 3, 'Kalungkan medali, arahkan finisher ke refreshment.', 'medal'],
                    ['Refreshment Finish', 2, 3, 'Air, buah, minuman isotonik.', 'water'],
                    ['Podium & LO Sponsor', 2, 2, 'Podium 15K, hadiah, pendamping sponsor/tamu.', 'trophy'],
                ],
                'Layanan' => [
                    ['Konsumsi Panitia & Tamu', 3, 4, 'Sarapan & makan siang panitia, distribusi konsumsi ke pos lapangan, konsumsi tamu/VIP & sponsor.', 'food'],
                    ['Info Desk / Help Desk', 2, 2, 'BIB hilang/rusak, pertanyaan peserta, lost & found.', 'info'],
                    ['Penitipan Barang', 2, 2, 'Penitipan dengan tag nomor BIB.', 'bag'],
                    ['Medis Race Village', 2, 2, 'Tim medis siaga di Race Village.', 'aid', null, '+ ambulans'],
                    ['MC & Sound', 2, 2, 'Briefing, hitung mundur, sebut finisher, podium.', 'mic'],
                    ['Dokumentasi', 2, 3, 'Foto/video start-finish, 1 orang di rute (mis. CP2).', 'camera'],
                ],
                'Pendukung' => [
                    ['Keamanan & Parkir', 3, 4, 'Linmas/pemuda setempat, penyeberangan jalan dekat SD.', 'shield'],
                    ['Logistik Race Village', 2, 2, 'Tenda, meja, distribusi perlengkapan pos jam 04.00–05.00.', 'box'],
                    ['Kebersihan', 2, 2, 'Sampah Race Village & sampah WS setelah race.', 'trash'],
                ],
            ],
            'Lapangan' => [
                'Komando Lapangan' => [
                    ['Koordinator Lapangan', 1, 1, 'Pantau semua pos via HT/WA grup.', 'radio'],
                ],
                'Marshal' => [
                    ['Marshal 1', 1, 1, $m, 'sign', ['0,3', '14,7'], 'Titik yang sama'],
                    ['Marshal 2', 1, 1, $m, 'sign', ['1,5', '13,5']],
                    ['Marshal 3', 2, 2, 'Titik simpang, pastikan arah tiap kategori benar.', 'sign', ['2', '8,1', '12,8']],
                    ['Marshal 4', 1, 1, $m, 'sign', ['2,15']],
                    ['Marshal 5', 1, 1, $m, 'sign', ['2,6']],
                    ['Marshal 6', 1, 2, $m, 'sign', ['3,4']],
                    ['Marshal 7', 1, 2, $m, 'sign', ['5,1']],
                    ['Marshal 8', 1, 2, $m, 'sign', ['7,56', '12,2']],
                    ['Marshal 9', 1, 1, $m, 'sign', ['8,5']],
                    ['Marshal 10', 1, 2, $m, 'sign', ['10,1']],
                    ['Marshal Rumah Terakhir', 1, 1, $m, 'sign', null, 'Rumah terakhir, sebelum Marshal 11'],
                    ['Marshal 11', 1, 2, $m, 'sign', ['11,75']],
                ],
                'Pos CP / WS / COP' => [
                    ['CP1 Melosor', 2, 2, '1 orang scanner BIB, 1 orang catat manual.', 'scan', ['5,7']],
                    ['WS 1 & 2', 4, 5, 'Satu lokasi, dilewati 2 kali. Air, buah, isotonik, es.', 'water', ['7,7', '12,3']],
                    ['COP Basecamp', 2, 2, 'Cut-off jam 09.00. Tegakkan cut-off, scan & catat peserta yang dihentikan. Di Basecamp juga ada pos medis.', 'clock', ['8,2']],
                    ['CP2 Top Keteri', 3, 3, 'Titik terjauh; scanner BIB + catat manual.', 'scan', ['11,3']],
                ],
                'Pendukung Lapangan' => [
                    ['Sweeper 7K', 2, 2, 'Di belakang peserta terakhir 7K, cabut marka rute 7K, lapor ke Race Control.', 'sweep'],
                ],
            ],
        ];
    }

    public function run(): void
    {
        $info = [
            'lokasi_race_village' => 'SD Negeri 4 Banyu Urip',
            'lokasi_rute' => 'Bukit Keteri, Gerung, Lombok Barat, NTB',
            'catatan_open_gate' => 'Open gate 1 jam sebelum start',
            'penyelenggara' => 'Komunitas Runger (Runners Gerung)',
            'roster_aktif' => true,
        ];

        // Event GTR yang sudah ada (slug lama/baru). Bila belum ada, buat event baru yang TIDAK
        // dipublikasikan & slug-nya bukan Event::GTR_SLUG supaya Event::gtrId() (modul timing) tidak berubah.
        $event = Event::whereIn('slug', [Event::GTR_SLUG, 'gerung-trail-run'])->orderByRaw('slug = ? desc', [Event::GTR_SLUG])->first()
            ?? Event::create([
                'slug' => 'gtr-2026-roster',
                'title' => 'Gerung Trail Run (GTR) 2026',
                'date' => '2026-11-29',
                'distance_text' => '7K & 15K',
                'location' => 'Bukit Keteri, Gerung',
                'is_published' => false,
            ]);
        $event->update($info + ['distance_text' => $event->distance_text ?: '7K & 15K']);

        if ($event->rosterAreas()->exists()) {
            $this->command?->info("Struktur roster \"{$event->title}\" sudah ada — tidak diubah.");

            return;
        }

        DB::transaction(function () use ($event) {
            $ai = 0;
            foreach ($this->structure() as $areaName => $groups) {
                $area = $event->rosterAreas()->create(['nama' => $areaName, 'urutan' => ++$ai]);
                $gi = 0;
                foreach ($groups as $groupName => $positions) {
                    $group = $area->groups()->create(['nama' => $groupName, 'urutan' => ++$gi]);
                    foreach ($positions as $pi => $p) {
                        $group->positions()->create([
                            'nama' => $p[0],
                            'min_petugas' => $p[1],
                            'max_petugas' => $p[2],
                            'deskripsi' => $p[3],
                            'ikon' => $p[4],
                            'km' => $p[5] ?? null,
                            'catatan_tambahan' => $p[6] ?? null,
                            'urutan' => $pi + 1,
                        ]);
                    }
                }
            }

            RosterConsumptionSetting::updateOrCreate(['event_id' => $event->id], [
                'tamu_tambahan' => 10,
                'air_per_orang' => 2,
                'jadwal' => RosterConsumptionSetting::DEFAULT_JADWAL,
                'catatan' => RosterConsumptionSetting::DEFAULT_CATATAN,
                'area_acuan_id' => $event->rosterAreas()->where('slug', 'lapangan')->value('id'),
            ]);
        });

        $this->command?->info("Struktur roster \"{$event->title}\" dibuat.");
    }
}
