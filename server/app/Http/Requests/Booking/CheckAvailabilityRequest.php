<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_date' => ['required', 'date_format:Y-m-d'],
            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'services.*.variation_option_ids' => ['sometimes', 'array'],
            'services.*.variation_option_ids.*' => ['integer', 'exists:variation_options,id'],
            'services.*.add_on_ids' => ['sometimes', 'array'],
            'services.*.add_on_ids.*' => ['integer', 'exists:add_ons,id'],
        ];
    }
}
