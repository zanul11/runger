<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReassignMarshalRequest;
use App\Http\Requests\Api\StoreMarshalRequest;
use App\Models\User;
use App\Services\MarshalService;
use Illuminate\Http\JsonResponse;

class MarshalController extends Controller
{
    public function __construct(private readonly MarshalService $marshals) {}

    /**
     * Buat user marshal + assign ke event & timing point. (admin only)
     */
    public function store(StoreMarshalRequest $request): JsonResponse
    {
        [$user, $assignment] = $this->marshals->createMarshal(
            $request->only(['name', 'username', 'password']),
            (int) $request->event_id,
            (int) $request->timing_point_id,
        );

        $assignment->loadMissing('timingPoint');

        return response()->json([
            'marshal' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'role' => $user->role,
            ],
            'assignment' => [
                'event_id' => $assignment->event_id,
                'timing_point_id' => $assignment->gtr_timing_point_id,
                'code' => $assignment->timingPoint?->code,
                'is_active' => $assignment->is_active,
            ],
        ], 201);
    }

    /**
     * Pindahkan marshal ke pos lain (nonaktifkan lama, aktifkan baru). (admin only)
     */
    public function reassign(ReassignMarshalRequest $request, User $marshal): JsonResponse
    {
        $assignment = $this->marshals->reassign(
            $marshal,
            (int) $request->event_id,
            (int) $request->timing_point_id,
        );

        $assignment->loadMissing('timingPoint');

        return response()->json([
            'marshal_id' => $marshal->id,
            'assignment' => [
                'event_id' => $assignment->event_id,
                'timing_point_id' => $assignment->gtr_timing_point_id,
                'code' => $assignment->timingPoint?->code,
                'is_active' => $assignment->is_active,
            ],
        ]);
    }
}
