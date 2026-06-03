<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Route;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class RouteSeeder extends Seeder
{
    public function run(): void
    {
        $longRun = Event::where('slug', 'long-run-10k-may')->first();
        if (! $longRun) {
            return;
        }

        // Copy legacy GPX from public/assets/ into managed storage (idempotent)
        $legacy = public_path('assets/Lr_runger_2.gpx');
        $relative = 'routes/gpx/Lr_runger_2.gpx';
        if (File::exists($legacy)) {
            Storage::disk('public')->makeDirectory('routes/gpx');
            $dest = Storage::disk('public')->path($relative);
            if (! File::exists($dest) || File::lastModified($legacy) > File::lastModified($dest)) {
                File::copy($legacy, $dest);
            }
        }

        // Routes::booted() auto-parses the file and populates all stats
        Route::updateOrCreate(
            ['slug' => 'long-run-10k-bukit-keteri'],
            [
                'event_id' => $longRun->id,
                'name' => 'Long Run 10K Route',
                'description' => 'Rute aktual hasil rekaman GPS dengan marker tiap KM.',
                'gpx_file' => $relative,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
