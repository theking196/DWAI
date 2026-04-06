<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCanonRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|in:note,character,location,item,event,lore,timeline_event,artifact',
            'content' => 'sometimes|required|string|max:50000',
            'tags' => 'sometimes|required|array',
            'tags.*' => 'string|max:50',
            'importance' => 'sometimes|required|string|in:minor,moderate,important,critical',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Invalid canon type',
            'content.max' => 'Content cannot exceed 50000 characters',
            'importance.in' => 'Importance must be: minor, moderate, important, or critical',
        ];
    }
}
