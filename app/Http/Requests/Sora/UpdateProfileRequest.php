<?php

declare(strict_types=1);

namespace App\Http\Requests\Sora;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => strtolower((string) $this->input('username')),
            ]);
        }

        if ($this->filled('phone')) {
            $this->merge([
                'phone' => $this->normalizePhone((string) $this->input('phone')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:50'],
            'last_name' => ['sometimes', 'required', 'string', 'max:50'],
            'username' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:30',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($this->user()?->id),
            ],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => [
                'nullable',
                'string',
                'phone:INTERNATIONAL',
                Rule::unique('users', 'phone')->ignore($this->user()?->id),
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
