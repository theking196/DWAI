<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateImageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'prompt' => 'required|string|min:1|max:1000',
            'count' => 'nullable|integer|min:1|max:4',
            'model' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'prompt.required' => 'Image prompt is required',
            'prompt.max' => 'Prompt cannot exceed 1000 characters',
            'count.max' => 'Maximum 4 images per request',
        ];
    }
}
