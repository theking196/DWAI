<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class CreateSessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|min:1',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|in:writing,brainstorm,edit,research',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Session name is required',
            'type.in' => 'Session type must be: writing, brainstorm, edit, or research',
        ];
    }
}
