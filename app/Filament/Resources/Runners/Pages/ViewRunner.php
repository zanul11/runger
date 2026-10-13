<?php
namespace App\Filament\Resources\Runners\Pages;
use App\Filament\Resources\Runners\RunnerResource;
use Filament\Resources\Pages\ViewRecord;
class ViewRunner extends ViewRecord
{
    protected static string $resource = RunnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RunnerResource::setPasswordAction(),
            RunnerResource::sendResetLinkAction(),
        ];
    }
}
