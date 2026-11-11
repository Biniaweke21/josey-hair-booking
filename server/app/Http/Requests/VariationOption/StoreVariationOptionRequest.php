<?php

namespace App\Http\Requests\VariationOption;

use Illuminate\Foundation\Http\FormRequest;

class StoreVariationOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('variation_types.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
