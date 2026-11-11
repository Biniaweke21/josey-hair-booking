<?php

namespace App\Http\Requests\AddOn;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('add_ons.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_modifier' => ['sometimes', 'numeric'],
            'duration_modifier_minutes' => ['sometimes', 'integer'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
