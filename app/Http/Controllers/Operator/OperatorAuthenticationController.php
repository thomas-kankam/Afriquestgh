<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailOrPhoneRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Operator\OperatorRegisterRequest;
use App\Http\Requests\Operator\UpdateProfileRequest;
use App\Models\Operator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class OperatorAuthenticationController extends Controller
{
    public function login(EmailOrPhoneRequest $request): JsonResponse
    {
        $operator = self::findActorByEmailOrPhone(Operator::class, $request->validated('emailOrPhone'));

        return self::sendActorOtp($operator, 'operator', 'login');
    }

    public function register(OperatorRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['profile_image'] = static::base64ImageDecode($data['profile_image'] ?? null);

        $operator = Operator::create([
            'operator_slug' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'email' => $data['email'],
            'organization' => $data['organization'] ?? null,
            'location' => $data['location'] ?? null,
            'status' => 'inactive',
            'is_verified' => false,
            'profile_image' => $data['profile_image'],
        ]);

        return self::sendActorOtp($operator, 'operator', 'registration');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $operator = self::findActorByEmailOrPhone(Operator::class, $data['emailOrPhone']);

        return self::verifyActorOtp(
            otp: $data['otp'],
            actor: $operator,
            guard: 'operator',
        );
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $operator = self::findActorByEmailOrPhone(Operator::class, $request->validated('emailOrPhone'));

        if (! $operator) {
            return self::sendActorOtp(null, 'operator', 'login');
        }

        $type = self::resolveActorOtpType($operator, 'operator');

        return self::sendActorOtp($operator, 'operator', $type);
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

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = collect($request->validated())->except(['phone_number'])->all();
        
        return self::updateActorProfile(
            request()->user(),
            'operator',
            $data
        );
    }
}
