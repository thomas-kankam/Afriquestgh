<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailOrPhoneRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Client\ClientRegisterRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ClientAuthenticationController extends Controller
{
    public function login(EmailOrPhoneRequest $request): JsonResponse
    {
        $client = self::findActorByEmailOrPhone(Client::class, $request->validated('emailOrPhone'));

        return self::sendActorOtp($client, 'client', 'login');
    }

    public function register(ClientRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['profile_image'] = static::base64ImageDecode($data['profile_image'] ?? null);
        $client = Client::create([
            'client_slug' => (string) Str::uuid(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'phone_number' => $data['phone_number'] ?? null,
            'email' => $data['email'],
            'location' => $data['location'] ?? null,
            'status' => 'active',
            'is_verified' => false,
            'profile_image' => $data['profile_image'],
        ]);

        return self::sendActorOtp($client, 'client', 'registration');
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $request->validated();
        $client = self::findActorByEmailOrPhone(Client::class, $data['emailOrPhone']);

        return self::verifyActorOtp(
            otp: (int) $data['otp'],
            actor: $client,
            guard: 'client',
            type: $data['type']
        );
    }

    public function resendOtp(EmailOrPhoneRequest $request): JsonResponse
    {
        $client = self::findActorByEmailOrPhone(Client::class, $request->validated('emailOrPhone'));

        if (! $client) {
            return self::sendActorOtp(null, 'client', 'login');
        }

        $type = $client->is_verified ? 'login' : 'registration';

        return self::sendActorOtp($client, 'client', $type);
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
