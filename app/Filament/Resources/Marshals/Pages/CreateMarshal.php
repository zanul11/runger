<?php

namespace App\Filament\Resources\Marshals\Pages;

use App\Filament\Resources\Marshals\MarshalResource;
use App\Models\Event;
use App\Models\User;
use App\Services\MarshalService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMarshal extends CreateRecord
{
    protected static string $resource = MarshalResource::class;

    /**
     * Marshal: user + assignment ke pos dalam satu transaksi (MarshalService).
     * Panitia: user saja (tanpa pos) — hanya checklist Persiapan.
     */
    protected function handleRecordCreation(array $data): Model
    {
        if (($data['role'] ?? User::ROLE_MARSHAL) === User::ROLE_PANITIA) {
            return User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => $data['password'],
                'role' => User::ROLE_PANITIA,
            ]);
        }

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
