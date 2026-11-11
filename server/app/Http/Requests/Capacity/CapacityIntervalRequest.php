<?php

namespace App\Http\Requests\Capacity;

use Illuminate\Foundation\Http\FormRequest;

class CapacityIntervalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reports.view');
    }

    public function rules(): array
    {
        return [
            'time' => ['required', 'date_format:H:i'],
        ];
    }
}
