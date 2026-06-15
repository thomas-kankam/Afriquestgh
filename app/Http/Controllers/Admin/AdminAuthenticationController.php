<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminRegisterRequest;
use App\Http\Requests\Auth\EmailOrPhoneRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminAuthenticationController extends Controller
{
    public function login(EmailOrPhoneRequest $request): JsonResponse
    {
        $admin = self::findActorByEmailOrPhone(Admin::class, $request->validated('emailOrPhone'));

        return self::sendActorOtp($admin, 'admin', 'login');
    }

    public function register(AdminRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $admin = Admin::create([
            'admin_slug' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'email' => $data['email'],
            'role_slug' => $data['role_slug'] ?? null,
            'password' => Hash::make(Str::random(64)),
            'status' => 'active',
        ]);

        return self::sendActorOtp($admin, 'admin', 'registration');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $admin = self::findActorByEmailOrPhone(Admin::class, $data['emailOrPhone']);

        return self::verifyActorOtp(
            otp: (int) $data['otp'],
            actor: $admin,
            guard: 'admin',
            type: $data['type']
        );
    }

    public function resendOtp(EmailOrPhoneRequest $request): JsonResponse
    {
        $admin = self::findActorByEmailOrPhone(Admin::class, $request->validated('emailOrPhone'));

        if (! $admin) {
            return self::sendActorOtp(null, 'admin', 'login');
        }

        $type = $admin->email_verified_at ? 'login' : 'registration';

        return self::sendActorOtp($admin, 'admin', $type);
    }

    public function logout(): JsonResponse
    {
        request()->user()->token()->revoke();

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Logout successful',
            status_code: (string) self::API_SUCCESS,
            data: []
        );
    }
}
