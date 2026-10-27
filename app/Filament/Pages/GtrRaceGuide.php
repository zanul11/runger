<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Generator Race Guide: slide carousel (1080×1350) yang isinya diambil dari konten admin
 * GTR (pengaturan, kategori, aturan, sponsor), bisa diedit teksnya lalu diunduh sebagai PNG.
 */
class GtrRaceGuide extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Race Guide';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.gtr-race-guide';

    public function getTitle(): string
    {
        return 'Generator Race Guide';
    }
}
