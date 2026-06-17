<?php

namespace App\Traits;

use App\Jobs\SendOtpEmailJob;
use App\Models\Actor;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;

trait AppNotifications
{
    use ApiTransformer, Helpers;

    protected static function checkOtp(string $guard, int $otp, int $actor_id, string $type): array
    {
        $otpValue = str_pad((string) $otp, 6, '0', STR_PAD_LEFT);

        $query = Otp::query()
            ->where('actor_id', $actor_id)
            ->where('guard', $guard)
            ->where('type', $type)
            ->where('token', $otpValue);

        $validOtp = (clone $query)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if ($validOtp) {
            return [
                'status' => 'valid',
                'channel' => $validOtp->channel,
                'otp' => $validOtp,
            ];
        }

        if ($query->where('expires_at', '<=', now())->exists()) {
            return ['status' => 'expired'];
        }

        return ['status' => 'not_found'];
    }

    public static function sendActorOtp(?Actor $actor, string $guard, string $type): JsonResponse
    {
        if (! $actor) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'User cannot be found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $otp = self::otpCode(
            type: $type,
            actor_id: $actor->id,
            channel: 'email',
            guard: $guard
        );

        SendOtpEmailJob::dispatch($actor->email, $otp, $type);

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: 'Otp sent to email successfully',
            status_code: (string) self::API_SUCCESS,
            data: $actor->toArray()
        );
    }

    protected static function verifyActorOtp(int $otp, ?Actor $actor, string $guard, string $type): JsonResponse
    {
        if (! $actor) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'User account cannot be found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $result = self::checkOtp(guard: $guard, otp: $otp, actor_id: $actor->id, type: $type);

        if ($result['status'] === 'expired') {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Otp expired',
                status_code: (string) self::API_FAIL,
                data: []
            );
        }

        if ($result['status'] === 'not_found') {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Otp not found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $result['otp']->delete();

        if ($guard === 'client' && $type === 'registration') {
            $actor->is_verified = true;
            $actor->verified_at = now();
            $actor->status = 'active';
            $actor->save();
        }

        $accessToken = self::apiToken($actor, $guard);

        $reason = $type === 'registration'
            ? 'Account verified successfully'
            : 'Login successful';

        $responseData = $guard === 'admin' && $actor instanceof \App\Models\Admin
            ? $actor->fresh(['role.permissions'])->toAuthArray()
            : $actor->fresh()->toArray();

        $responseData['token'] = $accessToken;

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: $reason,
            status_code: (string) self::API_SUCCESS,
            data: $responseData
        );
    }
}
