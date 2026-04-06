<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class UploadReferenceRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'image' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:10240',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'is_style_reference' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'Image file is required',
            'image.mimes' => 'Image must be: jpg, jpeg, png, gif, or webp',
            'image.max' => 'Image cannot exceed 10MB',
        ];
    }
}
