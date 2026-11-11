<?php

namespace App\Http\Requests\BusinessHourException;

use Illuminate\Foundation\Http\FormRequest;

class StoreBusinessHourExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'unique:business_hour_exceptions,date'],
            'is_closed' => ['required', 'boolean'],
            'open_time' => ['nullable', 'date_format:H:i', 'required_if:is_closed,false'],
            'close_time' => ['nullable', 'date_format:H:i', 'required_if:is_closed,false', 'after:open_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
