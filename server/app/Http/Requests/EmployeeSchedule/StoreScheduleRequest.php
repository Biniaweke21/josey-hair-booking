<?php

namespace App\Http\Requests\EmployeeSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.update');
    }

    public function rules(): array
    {
        return [
            'day_of_week' => [
                'required',
                'integer',
                'between:0,6',
                Rule::unique('employee_weekly_schedules', 'day_of_week')
                    ->where('employee_id', $this->route('employee')?->id)
                    ->whereNull('deleted_at'),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
