<?php

namespace App\Filament\Pages;

use App\Models\GtrCategory;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Url;

/**
 * Preview course map kategori persis seperti di halaman detail publik,
 * plus export gambar peta resolusi tinggi (dirender di halaman preview).
 */
class GtrCoursePreview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|\UnitEnum|null $navigationGroup = 'GTR';

    protected static ?string $navigationLabel = 'Preview Course Map';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.gtr-course-preview';

    #[Url]
    public ?string $category = null;

    public function getTitle(): string
    {
        return 'Preview Course Map';
    }

    public function mount(): void
    {
        $this->category ??= (string) GtrCategory::orderBy('sort_order')->value('id');
    }

    public function categories()
    {
        return GtrCategory::orderBy('sort_order')->get(['id', 'name', 'distance']);
    }

    public function previewUrl(): ?string
    {
        $cat = $this->category ? GtrCategory::find($this->category) : null;

        return $cat ? route('gtr.course-preview', $cat) : null;
    }
}
