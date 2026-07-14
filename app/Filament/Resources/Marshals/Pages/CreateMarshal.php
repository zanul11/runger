<?php

namespace App\Filament\Resources\Marshals\Pages;

use App\Filament\Resources\Marshals\MarshalResource;
use App\Models\Event;
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
        [$user] = app(MarshalService::class)->createMarshal(
            [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => $data['password'],
            ],
            (int) Event::gtrId(),
            (int) $data['timing_point_id'],
        );

        return $user;
    }
}
