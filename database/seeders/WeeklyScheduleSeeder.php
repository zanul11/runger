<?php

namespace Database\Seeders;

use App\Models\WeeklySchedule;
use Illuminate\Database\Seeder;

class WeeklyScheduleSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['title' => 'Friday Night Run', 'day_of_week' => 'Jumat', 'time' => '20:00', 'location' => 'Depan Kantor Bupati Lobar', 'description' => 'Lari rutin mingguan — 5K rolling route, all pace welcome.', 'tag' => 'Weekly · Night Run'],
            ['title' => 'Morning Sunday Long Run', 'day_of_week' => 'Minggu', 'time' => '05:30', 'location' => 'Depan Kantor Bupati Lobar', 'description' => 'Sesi long run mingguan — jarak menyesuaikan rute (8–15K).', 'tag' => 'Weekly · Long Run'],
        ];
        foreach ($rows as $i => $r) {
            WeeklySchedule::updateOrCreate(
                ['title' => $r['title']],
                array_merge($r, ['sort_order' => $i, 'is_active' => true])
            );
        }
    }
}
