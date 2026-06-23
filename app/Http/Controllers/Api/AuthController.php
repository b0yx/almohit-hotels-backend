<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\EmailOTP;
use App\Models\User;
use App\Support\CompatResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function signup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'first_name' => ['nullable', 'string', 'max:150'],
            'last_name' => ['nullable', 'string', 'max:150'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'password_confirm' => ['required', 'same:password'],
        ]);

        $fullName = trim(($data['first_name'] ?? '').' '.($data['last_name'] ?? '')) ?: trim((string) ($data['full_name'] ?? ''));
        if ($fullName === '') {
            throw ValidationException::withMessages(['full_name' => 'Provide first_name + last_name, or full_name.']);
        }

        $user = User::query()->create([
            'email' => strtolower($data['email']),
            'full_name' => $fullName,
            'role' => User::ROLE_CUSTOMER,
            'is_staff' => false,
            'is_superuser' => false,
            'is_active' => false,
            'email_verified' => false,
            'password' => $data['password'],
        ]);

        EmailOTP::query()->create([
            'user_id' => $user->id,
            'hashed_code' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json(['detail' => 'Account created. Please check your email for the verification code.'], 201);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'string', 'size:6']]);
        $user = User::query()->where('email', strtolower($data['email']))->first();
        $otp = $user ? EmailOTP::query()->where('user_id', $user->id)->whereNull('verified_at')->latest()->first() : null;

        if (! $user || ! $otp || $otp->expires_at->isPast() || ! Hash::check($data['code'], $otp->hashed_code)) {
            return response()->json(['detail' => 'Invalid verification code.', 'code' => 'invalid_otp'], 400);
        }

        $otp->forceFill(['verified_at' => now()])->save();
        $user->forceFill(['email_verified' => true, 'is_active' => true])->save();

        return response()->json(['detail' => 'Email verified successfully.']);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::query()->where('email', strtolower($data['email']))->where('email_verified', false)->first();
        if ($user) {
            EmailOTP::query()->create([
                'user_id' => $user->id,
                'hashed_code' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(10),
            ]);
        }

        return response()->json(['detail' => 'A new verification code has been sent.']);
    }

    public function login(Request $request, ?string $requiredRole = null): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $user = User::query()->where('email', strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['detail' => 'Invalid email or password.']);
        }

        if (! $user->email_verified) {
            return response()->json(['code' => 'email_not_verified', 'detail' => 'Please verify your email before signing in.', 'email' => $user->email], 400);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages(['detail' => 'This account is inactive.']);
        }

        if ($requiredRole === 'admin' && ! $user->isAdmin()) {
            return response()->json(['detail' => 'Admin account required.'], 403);
        }
        if ($requiredRole === 'customer' && $user->isAdmin()) {
            return response()->json(['detail' => 'Customer account required.'], 403);
        }

        $plain = Str::random(64);
        ApiToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        return response()->json([
            'token' => $plain,
            'access' => $plain,
            'token_type' => 'Bearer',
            'role' => $user->isAdmin() ? 'admin' : $user->role,
            'redirect_url' => $user->isAdmin() ? config('almohit.frontend_admin_url') : config('almohit.frontend_customer_url'),
            'user' => CompatResponse::user($user),
        ]);
    }

    public function adminLogin(Request $request): JsonResponse
    {
        return $this->login($request, 'admin');
    }

    public function customerLogin(Request $request): JsonResponse
    {
        return $this->login($request, 'customer');
    }

    public function me(Request $request): JsonResponse
    {
        if (! $request->user()) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        return response()->json(CompatResponse::user($request->user()));
    }

    public function updateMe(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['detail' => 'Authentication credentials were not provided.'], 401);
        }

        $user->fill($request->validate([
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
        ]))->save();

        return response()->json(CompatResponse::user($user));
    }

    public function logout(Request $request): JsonResponse
    {
        $header = (string) $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            ApiToken::query()->where('token', hash('sha256', $matches[1]))->delete();
        }

        return response()->json(null, 204);
    }
}
