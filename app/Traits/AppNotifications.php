<?php

namespace App\Traits;

use App\Jobs\SendOtpEmailJob;
use App\Models\Actor;
use App\Models\Otp;
use Illuminate\Http\JsonResponse;

trait AppNotifications
{
    use ApiTransformer, Helpers;

    protected static function checkOtp(string $guard, int $otp, int $actor_id, string $type): string
    {
        $token = Otp::query()
            ->where('actor_id', $actor_id)
            ->where('guard', $guard)
            ->where('type', $type)
            ->where('token', $otp)
            ->latest()
            ->first();

        if (! $token) {
            return self::API_NOT_FOUND;
        }

        if ($token->expires_at->isPast()) {
            return self::API_FAIL;
        }

        return $token->channel;
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

        if (! in_array($guard, ['admin', 'client'], true)) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Invalid guard',
                status_code: (string) self::API_FAIL,
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

        $channel = self::checkOtp(guard: $guard, otp: $otp, actor_id: $actor->id, type: $type);

        if ($channel === self::API_FAIL) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Otp expired',
                status_code: (string) self::API_FAIL,
                data: []
            );
        }

        if ($channel === self::API_NOT_FOUND) {
            return self::apiResponse(
                in_error: true,
                message: 'Action Unsuccessful',
                reason: 'Otp not found',
                status_code: (string) self::API_NOT_FOUND,
                data: []
            );
        }

        $actor = self::apiToken($actor, $guard);

        $reason = $type === 'registration'
            ? 'Account verified successfully'
            : 'Login successful';

        return self::apiResponse(
            in_error: false,
            message: 'Action Successful',
            reason: $reason,
            status_code: (string) self::API_SUCCESS,
            data: $actor->toArray()
        );
    }
}
