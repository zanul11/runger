<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class SponsorSeeder extends Seeder
{
    public function run(): void
    {
        $longRun = Event::where('slug', 'long-run-10k-may')->first();
        if ($longRun) {
            Sponsor::updateOrCreate(
                ['event_id' => $longRun->id, 'name' => 'Pocari Sweat'],
                [
                    'logo' => 'sponsors/pocari.png',
                    'link' => 'https://pocarisweat.id',
                    'tier' => 'title',
                    'note' => 'Refreshment partner — Free Refreshment setelah lari',
                    'sort_order' => 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
