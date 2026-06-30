<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * Profil marshal + pos aktif yang dikunci untuk device ini.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $assignment = $user->activeAssignment();
        $assignment?->loadMissing(['timingPoint', 'event']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'event' => $assignment?->event ? [
                'id' => $assignment->event->id,
                'slug' => $assignment->event->slug,
                'title' => $assignment->event->title,
            ] : null,
            'active_assignment' => $assignment && $assignment->timingPoint ? [
                'timing_point_id' => $assignment->gtr_timing_point_id,
                'code' => $assignment->timingPoint->code,
                'name' => $assignment->timingPoint->name,
                'type' => $assignment->timingPoint->type,
            ] : null,
        ]);
    }
}
