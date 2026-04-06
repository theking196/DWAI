<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateTextRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'prompt' => 'required|string|min:1|max:5000',
            'save_to_draft' => 'nullable|boolean',
            'model' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Prompt is required for text generation',
            'prompt.min' => 'Prompt cannot be empty',
            'prompt.max' => 'Prompt cannot exceed 5000 characters',
        ];
    }
}
