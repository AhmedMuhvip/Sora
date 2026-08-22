<?php

declare(strict_types=1);

namespace App\Http\Requests\Sora;

use Illuminate\Foundation\Http\FormRequest;

class AskQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1', 'max:1000'],
            'is_anonymous' => ['sometimes', 'boolean'],
        ];
    }
}
