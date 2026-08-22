<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SocialLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider' => [
                'required',
                Rule::in([
                    'google',
                ]),
            ],

            'access_token' => [
                'required',
                'string',
            ],

            'device_name' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }
}
