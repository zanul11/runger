<?php
namespace App\Filament\Resources\GtrRegistrations\Pages;
use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
class ViewGtrRegistration extends ViewRecord
{
    protected static string $resource = GtrRegistrationResource::class;
    protected function getHeaderActions(): array { return [EditAction::make()]; }
}
