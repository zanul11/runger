<?php

namespace App\Filament\Resources\GtrOverviews\Pages;

use App\Filament\Resources\GtrOverviews\GtrOverviewResource;
use App\Models\GtrOverview;
use Filament\Resources\Pages\ListRecords;

class ListGtrOverviews extends ListRecords
{
    protected static string $resource = GtrOverviewResource::class;

    public function mount(): void
    {
        parent::mount();

        // Singleton: langsung ke halaman edit satu-satunya baris.
        $this->redirect(GtrOverviewResource::getUrl('edit', ['record' => GtrOverview::current()]));
    }
}
