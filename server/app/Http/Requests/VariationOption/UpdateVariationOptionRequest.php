<?php

namespace App\Http\Requests\VariationOption;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVariationOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('variation_types.update');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
