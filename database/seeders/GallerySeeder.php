<?php

namespace Database\Seeders;

use App\Models\GalleryItem;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $files = collect(glob(public_path('gallery/*.{jpeg,jpg,png}'), GLOB_BRACE))
            ->map(fn ($p) => 'gallery/' . basename($p))
            ->values();

        // Plus 5 curated featured photos from /assets
        $featured = [
            ['image' => 'assets/g-squad-night1.jpeg', 'tag' => 'Night Run', 'caption' => 'Squad Jumat malam'],
            ['image' => 'assets/g-squad-night2.jpeg', 'tag' => 'Night Run', 'caption' => 'Long Run Sunday'],
            ['image' => 'assets/g-squad-night3.jpeg', 'tag' => 'Night Run', 'caption' => 'Crew after run'],
            ['image' => 'assets/g-grup-archway.jpeg', 'tag' => 'Trail', 'caption' => 'Bukit Keteri scout'],
            ['image' => 'assets/g-sunset.jpeg', 'tag' => 'Trail', 'caption' => 'Sunset pace'],
            ['image' => 'assets/g-tugu1.jpeg', 'tag' => 'Tugu', 'caption' => 'Tikum Tugu Gerung'],
        ];

        $order = 0;
        foreach ($featured as $f) {
            GalleryItem::updateOrCreate(['image' => $f['image']], array_merge($f, ['sort_order' => $order++, 'is_featured' => true]));
        }
        foreach ($files as $img) {
            GalleryItem::updateOrCreate(['image' => $img], ['tag' => 'WhatsApp', 'caption' => null, 'sort_order' => $order++, 'is_featured' => false]);
        }
    }
}
