<?php

namespace App\Http\Requests\BusinessHourException;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessHourExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    public function rules(): array
    {
        return [
            'date' => ['sometimes', 'date', Rule::unique('business_hour_exceptions', 'date')->ignore($this->route('business_hour_exception'))],
            'is_closed' => ['sometimes', 'boolean'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i', 'after:open_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
