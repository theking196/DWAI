<?php

namespace App\Http\Requests\DWAI;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'value' => 'required',
            'type' => 'nullable|string|in:string,boolean,integer,json',
        ];
    }

    public function messages(): array
    {
        return [
            'value.required' => 'Setting value is required',
            'type.in' => 'Type must be: string, boolean, integer, or json',
        ];
    }
}
