<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'household_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $household = Household::create([
                'name' => $data['household_name'] ?? $data['name']."'s Kitchen",
            ]);

            return User::create([
                'household_id' => $household->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
            ]);
        });

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid email or password.']);
        }

        return $this->tokenResponse($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->load('household')]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'household_name' => ['sometimes', 'required', 'string', 'max:100'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['sometimes', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($user, $data) {
            if (isset($data['email'])) {
                $data['email'] = strtolower($data['email']);
            }
            $user->update(collect($data)->only(['name', 'email', 'password'])->all());

            if (isset($data['household_name'])) {
                $user->household->update(['name' => $data['household_name']]);
            }
        });

        return response()->json(['user' => $user->fresh('household')]);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => $user->load('household'),
        ], $status);
    }
}
