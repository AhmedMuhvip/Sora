<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => $this->normalizePhone((string) $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:50',
            ],

            'last_name' => [
                'required',
                'string',
                'max:50',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],

            'phone' => [
                'nullable',
                'string',
                'phone:INTERNATIONAL',
                'unique:users,phone',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.phone' => 'The phone field must be a valid international phone number.',
        ];
    }

    private function normalizePhone(string $phone): string
    {
        try {
            return (new PhoneNumber($phone))->formatE164();
        } catch (Throwable) {
            return trim($phone);
        }
    }
}
