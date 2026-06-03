<?php

namespace Database\Seeders;

use App\Models\ContactChannel;
use App\Models\Event;
use App\Models\GalleryItem;
use App\Models\Member;
use App\Models\RaceCategory;
use App\Models\Route;
use App\Models\Rule;
use App\Models\Setting;
use App\Models\WeeklySchedule;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            EventsSeeder::class,
            SponsorSeeder::class,
            WeeklyScheduleSeeder::class,
            MemberSeeder::class,
            GallerySeeder::class,
            ContactSeeder::class,
            RouteSeeder::class,
        ]);
    }
}
