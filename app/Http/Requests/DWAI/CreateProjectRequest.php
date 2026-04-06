<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|min:1',
            'description' => 'nullable|string|max:2000',
            'type' => 'nullable|string|in:story,novel,script,worldbuilding,other',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required',
            'name.min' => 'Project name must be at least 1 character',
            'name.max' => 'Project name cannot exceed 255 characters',
            'type.in' => 'Project type must be: story, novel, script, worldbuilding, or other',
        ];
    }
}
