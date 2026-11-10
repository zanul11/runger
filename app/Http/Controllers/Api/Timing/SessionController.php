<?php

namespace App\Http\Controllers\Api\Timing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Timing\StartSessionRequest;
use App\Models\User;
use App\Services\GtrTimingSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public const TOKEN_PREFIX = 'timing:';

    /** Device id dari nama token Sanctum ("timing:<device_id>"). */
    public static function deviceId(Request $request): ?string
    {
        $name = $request->user()?->currentAccessToken()?->name ?? null;

        return is_string($name) && str_starts_with($name, self::TOKEN_PREFIX)
            ? Str::after($name, self::TOKEN_PREFIX)
            : null;
    }

    /** POST /api/timing/session — login marshal/admin, token per device + data sesi. */
    public function store(StartSessionRequest $request, GtrTimingSession $session): JsonResponse
    {
        $user = User::where('username', $request->username)->first();

        if (! $user || ! Hash::check($request->password, $user->password)
            || ! in_array($user->role, [User::ROLE_MARSHAL, User::ROLE_RACEPACK, User::ROLE_ADMIN], true)) {
            throw ValidationException::withMessages(['username' => ['Username atau password salah.']]);
        }

        $tokenName = self::TOKEN_PREFIX.$request->device_id;
        $user->tokens()->where('name', $tokenName)->delete(); // satu token per device
        $token = $user->createToken($tokenName, ['timing'])->plainTextToken;

        $this->touchDevice($user, $request->device_id);

        return response()->json(['token' => $token, 'device_id' => $request->device_id] + $session->payload($user));
    }

    /** GET /api/timing/session — muat ulang roster & pos (tanpa login ulang). */
    public function show(Request $request, GtrTimingSession $session): JsonResponse
    {
        $deviceId = self::deviceId($request);
        $this->touchDevice($request->user(), $deviceId);

        return response()->json(['device_id' => $deviceId] + $session->payload($request->user()));
    }

    /** DELETE /api/timing/session — logout device ini. */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function touchDevice(User $user, ?string $deviceId): void
    {
        if ($deviceId === null) {
            return;
        }

        $user->activeAssignment()?->forceFill(['device_id' => $deviceId, 'device_seen_at' => now()])->save();
    }
}
