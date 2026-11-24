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
     * Panitia / bendahara: user saja (tanpa pos) — Persiapan / Pengeluaran.
     */
    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? User::ROLE_MARSHAL;
        if (in_array($role, [User::ROLE_PANITIA, User::ROLE_BENDAHARA], true)) {
            return User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => $data['password'],
                'role' => $role,
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
