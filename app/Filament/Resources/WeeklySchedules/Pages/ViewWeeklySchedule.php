<?php

namespace App\Filament\Resources\WeeklySchedules\Pages;

use App\Filament\Resources\WeeklySchedules\WeeklyScheduleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWeeklySchedule extends ViewRecord
{
    protected static string $resource = WeeklyScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
