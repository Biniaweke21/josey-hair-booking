<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class AssignItemEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('bookings.update');
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
