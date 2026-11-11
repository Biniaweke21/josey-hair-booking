<?php

namespace App\Http\Requests\BusinessHour;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'between:0,6', 'distinct'],
            'hours.*.is_closed' => ['required', 'boolean'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('hours', []) as $index => $row) {
                if ($row['is_closed'] ?? false) {
                    continue;
                }

                if (empty($row['open_time']) || empty($row['close_time'])) {
                    $validator->errors()->add("hours.{$index}.open_time", 'Open and close time are required for a working day.');

                    continue;
                }

                if ($row['close_time'] <= $row['open_time']) {
                    $validator->errors()->add("hours.{$index}.close_time", 'Close time must be after open time.');
                }
            }
        });
    }
}
