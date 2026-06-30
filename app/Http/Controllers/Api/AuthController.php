<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login marshal/admin -> kembalikan bearer token Sanctum per device.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Satu token per device; token lama dengan nama device sama dicabut.
        $user->tokens()->where('name', $request->device_name)->delete();
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        request()->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }
}
