<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\RaceCategory;
use App\Models\Rule;
use Illuminate\Database\Seeder;

class EventsSeeder extends Seeder
{
    public function run(): void
    {
        // ===== LONG RUN 10K =====
        Event::updateOrCreate(
            ['slug' => 'long-run-10k-may'],
            [
                'title' => 'LONG RUN 10K',
                'subtitle' => 'Lari Bareng Sehat Bareng',
                'tag' => 'Long Run · Free Refreshment',
                'date' => '2026-05-24',
                'time' => '05:30',
                'status' => 'upcoming',
                'is_coming_soon' => false,
                'is_featured' => true,
                'is_published' => true,
                'distance_text' => '10K',
                'location' => 'Gerung, Lombok Barat',
                'tikum' => 'Depan Kantor Bupati Lobar',
                'tikum_lat' => -8.680761,
                'tikum_lng' => 116.136849,
                'briefing' => '05.15 WITA — Pemanasan & briefing rute',
                'pace' => 'All Pace Welcome',
                'fee' => 'Gratis · Free Refreshment',
                'note' => 'Long run mingguan — rute 10K menyusuri Gerung dengan peta interaktif & marker tiap KM. Bawa hidrasi yang cukup, gunakan sepatu yang nyaman. Lego lego setelah lari 🔥',
                'description' => '#LariBarengSehatBareng — Long Run 10K mingguan dari Runners Gerung. Rute panjang menyusuri Gerung dan sekitarnya, semua pace welcome. Setelah lari ada free refreshment by Pocari.',
                'poster_image' => 'events/poster/lr-10k.jpeg',
                'detail_template' => 'long_run',
                'detail_url' => null,
                'cta_primary_label' => 'Konfirmasi Gabung',
                'cta_primary_href' => 'https://www.instagram.com/runnersgerung/',
                'cta_ghost_label' => 'Buka di Maps',
                'cta_ghost_href' => 'https://www.google.com/maps/search/?api=1&query=-8.680761,116.136849',
                'sort_order' => 1,
            ]
        );

        // ===== GERUNG TRAIL RUN =====
        $trailRun = Event::updateOrCreate(
            ['slug' => 'gerung-trail-run'],
            [
                'title' => 'GERUNG TRAIL RUN',
                'subtitle' => '1st Edition · Bukit Keteri Trail',
                'tag' => 'Race · Trail · 1st Edition',
                'date' => '2026-11-29',
                'time' => '05:30',
                'status' => 'coming_soon',
                'is_coming_soon' => true,
                'is_featured' => true,
                'is_published' => true,
                'distance_text' => '5K · 12K · 21K',
                'location' => 'Bukit Keteri, Gerung — Lombok Barat',
                'tikum' => 'Bukit Keteri, Gerung',
                'briefing' => 'TBA',
                'pace' => 'Race Format · All Pace',
                'fee' => 'TBA',
                'note' => 'Race trail perdana yang digelar Runners Gerung di Bukit Keteri. Detail kategori, harga, dan rute diumumkan menjelang event. Pantau IG @runnersgerung.',
                'description' => 'Edisi perdana race trail Runners Gerung di Bukit Keteri. Rute melintasi punggung bukit, hutan rendah, dan jalur pedesaan dengan panorama Lombok Barat saat matahari terbit.',
                'hero_video' => 'assets/gtr/video-gtr.mp4',
                'poster_image' => null,
                'detail_template' => 'trail_run',
                'detail_url' => null,
                'cta_primary_label' => 'Notify via Instagram',
                'cta_primary_href' => 'https://www.instagram.com/runnersgerung/',
                'cta_ghost_label' => 'Preview Halaman Event',
                'cta_ghost_href' => null,
                'sort_order' => 2,
            ]
        );

        // Race categories for trail run
        $categories = [
            ['code' => '5K', 'name' => 'Sukawana Trail Run', 'distance_km' => 5, 'difficulty_level' => 0, 'start_time' => '06:00', 'duration' => '1.5 Jam', 'elevation_gain' => '+80 M', 'description' => 'Rute pendek dan ramah pemula, cocok untuk family run.', 'image' => 'assets/g-grup-archway.jpeg', 'cut_off' => '1.5 jam', 'age_minimum' => '12+'],
            ['code' => '12K', 'name' => 'Banyumulek Trail Run', 'distance_km' => 12, 'difficulty_level' => 1, 'start_time' => '05:45', 'duration' => '3 Jam', 'elevation_gain' => '+280 M', 'description' => 'Dynamic intro ke mountain racing — elevasi nyata, real challenge dengan rute punggung bukit.', 'image' => 'assets/g-tugu1.jpeg', 'cut_off' => '3 jam', 'age_minimum' => '16+'],
            ['code' => '21K', 'name' => 'Sekotong Trail Ultra', 'distance_km' => 21, 'difficulty_level' => 2, 'start_time' => '05:30', 'duration' => '5 Jam', 'elevation_gain' => '+500 M', 'description' => 'Test of endurance, elevation, dan race strategy — kategori utama dengan finish di pesisir.', 'image' => 'assets/g-sunset.jpeg', 'cut_off' => '5 jam', 'age_minimum' => '18+'],
        ];

        foreach ($categories as $i => $c) {
            RaceCategory::updateOrCreate(
                ['event_id' => $trailRun->id, 'code' => $c['code']],
                array_merge($c, ['event_id' => $trailRun->id, 'sort_order' => $i])
            );
        }

        // Rules for trail run
        $rules = [
            ['number' => 1, 'title' => 'Usia Minimum', 'body' => '5K: 12 tahun · 12K: 16 tahun · 21K Ultra: 18 tahun, dengan persetujuan orang tua / wali untuk kategori 5K & 12K.'],
            ['number' => 2, 'title' => 'Mandatory Gear', 'body' => 'Hidrasi minimal 500ml (5K), 1L (12K), 2L (21K), peluit, headlamp untuk start sebelum sunrise, sepatu trail wajib.'],
            ['number' => 3, 'title' => 'BIB Collection', 'body' => 'BIB diambil sehari sebelum race (28 November) di race center sekitar Bukit Keteri. Wajib bawa ID & bukti registrasi. Tidak ada race day BIB.'],
            ['number' => 4, 'title' => 'Cut-Off Time', 'body' => '5K: 1.5 jam · 12K: 3 jam · 21K: 5 jam dari flag-off masing-masing kategori. Peserta lewat COT diarahkan sweeper.'],
            ['number' => 5, 'title' => 'Water Station', 'body' => 'WS tersedia setiap 3–5 km untuk semua kategori. Isi botol pribadi — single use plastic dilarang di seluruh rute.'],
            ['number' => 6, 'title' => 'Litter & Environment', 'body' => 'Buang sampah pada drop point WS. Peserta tertangkap littering akan DQ otomatis. Leave No Trace policy.'],
            ['number' => 7, 'title' => 'Safety & Medic', 'body' => 'Tim medis siaga di tiap WS dan finish line. Peserta diperbolehkan retire kapan saja — hubungi marshal terdekat.'],
            ['number' => 8, 'title' => 'Transfer & Refund', 'body' => 'Transfer BIB diperbolehkan sampai H-14. Refund hanya untuk pembatalan event resmi oleh panitia.'],
        ];

        foreach ($rules as $i => $r) {
            Rule::updateOrCreate(
                ['event_id' => $trailRun->id, 'number' => $r['number']],
                array_merge($r, ['event_id' => $trailRun->id, 'sort_order' => $i, 'is_active' => true])
            );
        }
    }
}
