<?php

namespace Database\Seeders;

use App\Models\GtrCategory;
use Illuminate\Database\Seeder;

class GtrCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            [
                'slug' => '7k',
                'name' => 'Keteri Trail Run',
                'distance' => '7 KM',
                'tag' => '7K Trail',
                'color' => '#22c55e',
                'description' => 'Rute pendek dan scenic — pas untuk pelari trail pemula maupun yang mengejar kecepatan.',
                'price_early_bird' => 200000,
                'price_normal' => 250000,
                'early_bird_until' => '2026-09-30',
                'start_time' => '06:00 WITA',
                'cut_off_time' => '2 Jam',
                'elevation_gain' => '+344 M',
                'water_station' => '2 Pos',
                'award_prize' => 'Medali Finisher + E-Certificate',
                'mandatory_gear' => ['Hidrasi minimal 1 Liter', 'Peluit', 'Sepatu trail', 'Topi / buff', 'HP untuk emergency'],
                'rundown' => [
                    ['time' => '28 Nov · 14.00–18.00', 'activity' => 'Race Pack Collection'],
                    ['time' => '29 Nov · 05.15', 'activity' => 'Peserta tiba di venue'],
                    ['time' => '05.45', 'activity' => 'Briefing & pemanasan'],
                    ['time' => '06.00', 'activity' => 'Flag-off 7K'],
                    ['time' => '08.00', 'activity' => 'Cut-off time'],
                    ['time' => '08.30', 'activity' => 'Awarding & doorprize'],
                ],
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'slug' => '15k',
                'name' => 'Keteri Trail Challenge',
                'distance' => '15 KM',
                'tag' => '15K Trail',
                'color' => '#ef4444',
                'description' => 'Tantangan endurance, elevasi, dan strategi race menembus jalur Bukit Keteri & pedesaan Gerung.',
                'price_early_bird' => 250000,
                'price_normal' => 300000,
                'early_bird_until' => '2026-09-30',
                'start_time' => '05:30 WITA',
                'cut_off_time' => '4 Jam',
                'elevation_gain' => '+600 M',
                'water_station' => '4 Pos',
                'award_prize' => 'Medali Finisher, E-Certificate & Hadiah Top 3 Putra/Putri',
                'mandatory_gear' => ['Hidrasi minimal 1.5 Liter', 'Peluit', 'Headlamp', 'Sepatu trail', 'Jaket / windbreaker', 'HP + powerbank', 'Emergency blanket'],
                'rundown' => [
                    ['time' => '28 Nov · 14.00–18.00', 'activity' => 'Race Pack Collection'],
                    ['time' => '29 Nov · 05.00', 'activity' => 'Peserta tiba di venue'],
                    ['time' => '05.15', 'activity' => 'Briefing & pemanasan'],
                    ['time' => '05.30', 'activity' => 'Flag-off 15K'],
                    ['time' => '09.30', 'activity' => 'Cut-off time'],
                    ['time' => '10.00', 'activity' => 'Awarding & doorprize'],
                ],
                'sort_order' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($rows as $row) {
            GtrCategory::updateOrCreate(['slug' => $row['slug']], $row);
        }
    }
}
