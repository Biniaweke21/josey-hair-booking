<?php

namespace App\Http\Requests\ServiceVariation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SetVariationOptionAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('services.update');
    }

    public function rules(): array
    {
        return [
            'price_adjustment' => ['required', 'numeric'],
            'duration_adjustment_minutes' => ['required', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $service = $this->route('service');
            $option = $this->route('variationOption');

            if (! $service || ! $option) {
                return;
            }

            $typeAttached = $service->variationTypes()
                ->where('variation_types.id', $option->variation_type_id)
                ->exists();

            if (! $typeAttached) {
                $validator->errors()->add(
                    'variation_option_id',
                    'This service does not use the variation type this value belongs to. Attach the type first.',
                );
            }
        });
    }
}
