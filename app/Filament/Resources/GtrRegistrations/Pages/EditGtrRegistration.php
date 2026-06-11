<?php
namespace App\Filament\Resources\GtrRegistrations\Pages;
use App\Filament\Resources\GtrRegistrations\GtrRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
class EditGtrRegistration extends EditRecord
{
    protected static string $resource = GtrRegistrationResource::class;
    protected function getHeaderActions(): array { return [ViewAction::make(), DeleteAction::make()]; }
}
