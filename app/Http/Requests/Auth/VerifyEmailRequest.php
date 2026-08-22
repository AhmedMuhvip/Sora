<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],

            'code' => [
                'required',
                'digits:6',
            ],

            'device_name' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }
}
