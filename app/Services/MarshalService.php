<?php

namespace App\Services;

use App\Models\GtrTimingPoint;
use App\Models\GtrTimingPointAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Logika penugasan marshal ke pos timing.
 *
 * Invarian utama: satu marshal hanya boleh memiliki SATU assignment
 * is_active=true per event. Semua mutasi melewati service ini agar invarian
 * itu terjaga (dipakai API admin maupun Filament).
 */
class MarshalService
{
    /**
     * Buat user marshal baru lalu tugaskan ke event + timing point.
     */
    public function createMarshal(array $attributes, int $eventId, int $timingPointId): array
    {
        return DB::transaction(function () use ($attributes, $eventId, $timingPointId) {
            $user = User::create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'], // di-hash oleh cast 'hashed'
                'role' => User::ROLE_MARSHAL,
            ]);

            $assignment = $this->assign($user, $eventId, $timingPointId);

            return [$user, $assignment];
        });
    }

    /**
     * Tugaskan marshal ke pos: nonaktifkan semua assignment aktif lama di event
     * yang sama, lalu aktifkan (atau buat) yang baru.
     */
    public function assign(User $user, int $eventId, int $timingPointId): GtrTimingPointAssignment
    {
        return DB::transaction(function () use ($user, $eventId, $timingPointId) {
            // Pastikan timing point milik event yang sama.
            GtrTimingPoint::where('id', $timingPointId)
                ->where('event_id', $eventId)
                ->firstOrFail();

            // Nonaktifkan assignment aktif lain marshal ini di event ini.
            $user->timingPointAssignments()
                ->where('event_id', $eventId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // updateOrCreate supaya tidak menumpuk baris untuk pos yang sama.
            $assignment = $user->timingPointAssignments()->updateOrCreate(
                [
                    'event_id' => $eventId,
                    'gtr_timing_point_id' => $timingPointId,
                ],
                [
                    'is_active' => true,
                    'assigned_at' => now(),
                ],
            );

            return $assignment;
        });
    }

    /**
     * Pindahkan marshal ke pos lain (nonaktifkan lama, aktifkan baru).
     */
    public function reassign(User $user, int $eventId, int $timingPointId): GtrTimingPointAssignment
    {
        return $this->assign($user, $eventId, $timingPointId);
    }
}
