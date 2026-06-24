<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\AuditLog;
use App\Models\EmailOTP;
use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\BrevoMailService;
use App\Support\CompatResponse;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $code = $this->sendEmailVerificationOtp($user);

        if (app()->environment('local', 'testing')) {
            return response()->json(['detail' => 'Account created. Please check your email for the verification code.', 'debug_code' => $code], 201);
        }

        return response()->json(['detail' => 'Account created. Please check your email for the verification code.'], 201);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'string', 'size:6']]);
        $email = strtolower($data['email']);

        $limiter = app(RateLimiter::class);
        $otpKey = 'otp-attempt:'.$email;

        if ($limiter->tooManyAttempts($otpKey, 5)) {
            $seconds = $limiter->availableIn($otpKey);
            return response()->json(['detail' => 'Too many attempts. Try again in '.$seconds.' seconds.', 'code' => 'otp_locked'], 429);
        }

        $user = User::query()->where('email', $email)->first();
        $otp = $user ? EmailOTP::query()->where('user_id', $user->id)->whereNull('verified_at')->latest()->first() : null;

        if (! $user || ! $otp || $otp->expires_at->isPast() || ! Hash::check($data['code'], $otp->hashed_code)) {
            $limiter->hit($otpKey, 900);
            return response()->json(['detail' => 'Invalid verification code.', 'code' => 'invalid_otp'], 400);
        }

        $limiter->clear($otpKey);
        $otp->forceFill(['verified_at' => now()])->save();
        $user->forceFill(['email_verified' => true, 'is_active' => true])->save();

        return response()->json(['detail' => 'Email verified successfully.']);
    }

    public function resendOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($data['email']);

        $limiter = app(RateLimiter::class);
        $resendKey = 'otp-resend:'.$email;

        if ($limiter->tooManyAttempts($resendKey, 3)) {
            $seconds = $limiter->availableIn($resendKey);
            return response()->json(['detail' => 'Too many resend requests. Try again in '.$seconds.' seconds.', 'code' => 'resend_locked'], 429);
        }

        $user = User::query()->where('email', $email)->where('email_verified', false)->first();
        if ($user) {
            $this->sendEmailVerificationOtp($user);
        }

        $limiter->hit($resendKey, 60);

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
            $code = $this->sendEmailVerificationOtp($user);

            $response = ['code' => 'email_not_verified', 'detail' => 'Please verify your email before signing in.', 'email' => $user->email];

            if (app()->environment('local', 'testing')) {
                $response['debug_code'] = $code;
            }

            return response()->json($response, 400);
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

        $sanctumToken = $user->createToken('auth_token')->plainTextToken;
        $plain = Str::random(64);
        ApiToken::query()->create(['user_id' => $user->id, 'token' => hash('sha256', $plain)]);

        $userResponse = CompatResponse::user($user);
        $userResponse['name'] = $user->full_name;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $sanctumToken,
            'access' => $plain,
            'token_type' => 'Bearer',
            'role' => $user->isAdmin() ? 'admin' : $user->role,
            'redirect_url' => $user->isAdmin() ? config('almohit.frontend_admin_url') : config('almohit.frontend_customer_url'),
            'user' => $userResponse,
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
        if (preg_match('/^(?:Bearer|Token)\s+(.+)$/i', $header, $matches)) {
            $token = $matches[1];
            if (str_contains($token, '|')) {
                $pat = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
                if ($pat) {
                    $pat->delete();
                }
            } else {
                ApiToken::query()->where('token', hash('sha256', $token))->delete();
            }
        }

        return response()->json(null, 204);
    }

    public function activateUser(int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $wasActive = $user->is_active;
        $user->forceFill(['is_active' => true])->save();

        AuditLog::query()->create([
            'action' => 'activate',
            'content_type' => 'user',
            'object_id' => (string) $user->id,
            'object_repr' => $user->email,
            'actor_id' => request()->user()?->id,
            'actor_email' => request()->user()?->email ?? '',
            'actor_name' => request()->user()?->full_name ?? '',
            'changes' => ['is_active' => ['old' => $wasActive, 'new' => true]],
            'request_method' => request()->method(),
            'request_path' => request()->path(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return response()->json(CompatResponse::user($user));
    }

    public function deactivateUser(int $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $wasActive = $user->is_active;
        $user->forceFill(['is_active' => false])->save();

        AuditLog::query()->create([
            'action' => 'deactivate',
            'content_type' => 'user',
            'object_id' => (string) $user->id,
            'object_repr' => $user->email,
            'actor_id' => request()->user()?->id,
            'actor_email' => request()->user()?->email ?? '',
            'actor_name' => request()->user()?->full_name ?? '',
            'changes' => ['is_active' => ['old' => $wasActive, 'new' => false]],
            'request_method' => request()->method(),
            'request_path' => request()->path(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);

        return response()->json(CompatResponse::user($user));
    }

    public function changeUserRole(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['role' => ['required', 'string', 'in:customer,staff,admin']]);
        $user = User::query()->findOrFail($id);
        $oldRole = $user->role;
        $user->forceFill([
            'role' => $data['role'],
            'is_staff' => $data['role'] === 'admin' || $data['role'] === 'staff',
        ])->save();

        AuditLog::query()->create([
            'action' => 'change_role',
            'content_type' => 'user',
            'object_id' => (string) $user->id,
            'object_repr' => $user->email,
            'actor_id' => $request->user()?->id,
            'actor_email' => $request->user()?->email ?? '',
            'actor_name' => $request->user()?->full_name ?? '',
            'changes' => ['role' => ['old' => $oldRole, 'new' => $data['role']]],
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        return response()->json(CompatResponse::user($user));
    }

    public function resetUserPassword(Request $request, int $id): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8']]);
        $user = User::query()->findOrFail($id);
        $user->forceFill(['password' => $data['password']])->save();

        AuditLog::query()->create([
            'action' => 'reset_password',
            'content_type' => 'user',
            'object_id' => (string) $user->id,
            'object_repr' => $user->email,
            'actor_id' => $request->user()?->id,
            'actor_email' => $request->user()?->email ?? '',
            'actor_name' => $request->user()?->full_name ?? '',
            'changes' => ['password_reset' => true],
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);

        app(BrevoMailService::class)->sendPasswordChangedByAdmin($user->email, $user->full_name);

        return response()->json(['detail' => 'Password updated successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $email = strtolower($data['email']);
        $user = User::query()->where('email', $email)->first();

        $debugCode = null;

        if ($user) {
            PasswordResetOtp::query()
                ->where('email', $email)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now()]);

            $code = (string) random_int(100000, 999999);
            PasswordResetOtp::query()->create([
                'email' => $email,
                'otp_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(10),
            ]);

            app(BrevoMailService::class)->sendOtp($email, 'Reset Your Password', $code, 'password_reset');
            $debugCode = $code;
        }

        $response = ['detail' => 'If the email exists, an OTP has been sent.'];

        if (app()->environment('local', 'testing') && $debugCode) {
            $response['debug_code'] = $debugCode;
        }

        return response()->json($response);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'string', 'size:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($data['email']);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return response()->json(['detail' => 'Invalid or expired OTP.'], 400);
        }

        $otp = PasswordResetOtp::query()
            ->where('email', $email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (! $otp) {
            return response()->json(['detail' => 'Invalid or expired OTP.'], 400);
        }

        if ($otp->attempts >= 5) {
            return response()->json(['detail' => 'Too many attempts. Please request a new OTP.', 'code' => 'otp_locked'], 429);
        }

        if (! Hash::check($data['code'], $otp->otp_hash)) {
            $otp->increment('attempts');
            return response()->json(['detail' => 'Invalid or expired OTP.', 'code' => 'invalid_otp'], 400);
        }

        DB::transaction(function () use ($otp, $user, $data): void {
            $otp->forceFill(['used_at' => now(), 'attempts' => 0])->save();
            $user->forceFill(['password' => $data['password']])->save();
        });

        return response()->json(['detail' => 'Password has been reset successfully.']);
    }

    private function sendEmailVerificationOtp(User $user): string
    {
        EmailOTP::query()
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->update(['verified_at' => now()]);

        $code = (string) random_int(100000, 999999);

        EmailOTP::query()->create([
            'user_id' => $user->id,
            'hashed_code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        app(BrevoMailService::class)->sendOtp(
            $user->email,
            'Verify Your Email Address',
            $code,
            'email_verification',
        );

        return $code;
    }
}
