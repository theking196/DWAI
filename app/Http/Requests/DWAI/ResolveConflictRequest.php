<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class ResolveConflictRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
