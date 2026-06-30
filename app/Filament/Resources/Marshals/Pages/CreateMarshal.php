<?php

namespace App\Filament\Resources\Marshals\Pages;

use App\Filament\Resources\Marshals\MarshalResource;
use App\Models\GtrTimingPoint;
use App\Services\MarshalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMarshal extends CreateRecord
{
    protected static string $resource = MarshalResource::class;

    /**
     * Buat user marshal + assignment ke pos dalam satu transaksi (MarshalService).
     */
    protected function handleRecordCreation(array $data): Model
    {
        $tp = GtrTimingPoint::findOrFail($data['timing_point_id']);

        [$user] = app(MarshalService::class)->createMarshal(
            [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ],
            $tp->event_id,
            $tp->id,
        );

        return $user;
    }
}
