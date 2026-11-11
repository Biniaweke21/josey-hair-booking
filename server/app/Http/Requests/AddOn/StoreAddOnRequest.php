<?php

namespace App\Http\Requests\AddOn;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('add_ons.create');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_modifier' => ['required', 'numeric'],
            'duration_modifier_minutes' => ['required', 'integer'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
