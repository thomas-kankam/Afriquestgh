<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'emailOrPhone' => ['required', 'string', 'max:255'],
            'otp' => ['required', 'integer', 'digits:4'],
            'type' => ['required', 'string', 'in:login,registration'],
        ];
    }
}
