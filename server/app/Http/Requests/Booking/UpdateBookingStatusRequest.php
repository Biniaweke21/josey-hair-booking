<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bookings.update');
    }

    public function rules(): array
    {
        return [
            // Bookings are created confirmed - there's no approval step, so
            // 'confirmed' is never a valid target here. 'checked_in' and
            // 'completed' only via the dedicated check-in/check-out endpoints,
            // so the thank-you notification always fires on checkout.
            'status' => ['required', Rule::in(['cancelled', 'no_show'])],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
