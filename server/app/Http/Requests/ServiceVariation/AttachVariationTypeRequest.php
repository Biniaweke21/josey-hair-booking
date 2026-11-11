<?php

namespace App\Http\Requests\ServiceVariation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachVariationTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('services.update');
    }

    public function rules(): array
    {
        return [
            'variation_type_id' => [
                'required',
                'integer',
                'exists:variation_types,id',
                Rule::unique('service_variation_types', 'variation_type_id')
                    ->where('service_id', $this->route('service')?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
