<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.name' => ['required', 'string', 'max:255'],
            // Email is required: it's how we send booking confirmation,
            // reminder and thank-you notifications.
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:30'],

            'booking_date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],

            'services' => ['required', 'array', 'min:1'],
            'services.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'services.*.variation_option_ids' => ['sometimes', 'array'],
            'services.*.variation_option_ids.*' => ['integer', 'exists:variation_options,id'],
            'services.*.add_on_ids' => ['sometimes', 'array'],
            'services.*.add_on_ids.*' => ['integer', 'exists:add_ons,id'],

            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer.email.required' => 'An email address is required so we can send your confirmation and reminders.',
        ];
    }
}
