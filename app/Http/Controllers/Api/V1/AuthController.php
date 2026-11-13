<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! $user->password || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => trans('auth.failed')]);
        }

        return $this->tokenResponse($user, $credentials['device_name'] ?? 'mobile');
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->tokenResponse($user, $data['device_name'] ?? 'mobile', 201);
    }

    public function googleExchange(Request $request): JsonResponse
    {
        $code = $request->validate(['code' => ['required', 'string', 'max:100']])['code'];

        $userId = Cache::pull('google_mobile_code:'.$code);
        $user = $userId ? User::find($userId) : null;

        if (! $user) {
            throw ValidationException::withMessages(['code' => 'Přihlášení přes Google vypršelo, zkuste to prosím znovu.']);
        }

        return $this->tokenResponse($user, $request->input('device_name', 'mobile'));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => ApiPresenter::user($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Odhlášeno.']);
    }

    private function tokenResponse(User $user, string $deviceName, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken($deviceName)->plainTextToken,
            'user' => ApiPresenter::user($user),
        ], $status);
    }
}
