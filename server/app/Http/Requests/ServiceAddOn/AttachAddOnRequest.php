<?php

namespace App\Http\Requests\ServiceAddOn;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachAddOnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('services.update');
    }

    public function rules(): array
    {
        return [
            'add_on_id' => [
                'required',
                'integer',
                'exists:add_ons,id',
                Rule::unique('service_add_ons', 'add_on_id')
                    ->where('service_id', $this->route('service')?->id),
            ],
        ];
    }
}
