<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'locale' => ['nullable', Rule::in(SetLocale::SUPPORTED)],
        ]);

        $user = DB::transaction(function () use ($data) {
            $household = Household::create([
                'name' => $data['household_name'] ?? __(":name's Kitchen", ['name' => $data['name']]),
            ]);

            return User::create([
                'household_id' => $household->id,
                'name' => $data['name'],
                'email' => strtolower($data['email']),
                'password' => $data['password'],
                'locale' => $data['locale'] ?? app()->getLocale(),
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
            throw ValidationException::withMessages(['email' => __('Invalid email or password.')]);
        }

        return $this->tokenResponse($user);
    }

    /** Public settings the login screen needs. */
    public function config(): JsonResponse
    {
        return response()->json(['google_client_id' => config('services.google.client_id') ?: null]);
    }

    /**
     * Sign in with a Google ID token. The token is checked with Google (issuer, audience = our client id,
     * verified email, not expired). Existing accounts with the same email are linked.
     */
    public function google(Request $request): JsonResponse
    {
        $clientId = config('services.google.client_id');
        abort_unless($clientId, 404);
        $credential = $request->validate(['credential' => ['required', 'string', 'max:4096']])['credential'];

        $info = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $credential]);
        $claims = $info->successful() ? $info->json() : [];
        $valid = ($claims['aud'] ?? null) === $clientId
            && in_array($claims['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            && in_array($claims['email_verified'] ?? null, [true, 'true'], true)
            && (int) ($claims['exp'] ?? 0) > time()
            && ! empty($claims['sub']) && ! empty($claims['email']);

        if (! $valid) {
            throw ValidationException::withMessages(['google' => __('Google sign-in failed. Please try again.')]);
        }

        $email = strtolower($claims['email']);
        $user = User::where('google_id', $claims['sub'])->first() ?? User::where('email', $email)->first();

        if ($user) {
            $user->google_id ??= $claims['sub'];
            $user->save();
        } else {
            $name = $claims['name'] ?? Str::before($email, '@');
            $user = DB::transaction(fn () => User::create([
                'household_id' => Household::create(['name' => __(":name's Kitchen", ['name' => $name])])->id,
                'name' => $name,
                'email' => $email,
                'google_id' => $claims['sub'],
                'password' => Str::random(40), // never used; they sign in with Google
                'locale' => app()->getLocale(),
            ]));
        }

        return $this->tokenResponse($user, $user->wasRecentlyCreated ? 201 : 200);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('Logged out.')]);
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
            'locale' => ['sometimes', Rule::in(SetLocale::SUPPORTED)],
        ]);

        DB::transaction(function () use ($user, $data) {
            if (isset($data['email'])) {
                $data['email'] = strtolower($data['email']);
            }
            $user->update(collect($data)->only(['name', 'email', 'password', 'locale'])->all());

            if (isset($data['household_name'])) {
                $user->household->update(['name' => $data['household_name']]);
            }
        });

        return response()->json(['user' => $user->fresh('household')]);
    }

    /**
     * Delete the account and everything in it, for good (required by app stores).
     * Typing the account's email confirms it, which works for password and Google accounts alike.
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate(['confirm' => ['required', 'string']]);
        if (strtolower(trim($request->confirm)) !== $user->email) {
            throw ValidationException::withMessages(['confirm' => __('Type your email exactly to confirm.')]);
        }

        $household = $user->household;
        $photos = $household->users()->count() === 1
            ? Recipe::where('household_id', $household->id)->whereNotNull('image_path')->pluck('image_path')->all()
            : [];

        DB::transaction(function () use ($user, $household) {
            $user->tokens()->delete();
            $user->delete(); // coach logs go with the user
            if ($household->users()->doesntExist()) {
                $household->delete(); // kitchen, recipes, plans and lists go with the household
            }
        });
        Storage::disk('public')->delete($photos);

        return response()->json(['message' => __('Your account and data have been deleted.')]);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'token' => $user->createToken('app')->plainTextToken,
            'user' => $user->load('household'),
        ], $status);
    }
}
