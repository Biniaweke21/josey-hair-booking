<?php

namespace App\Http\Requests\EmployeeSchedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('employees.update');
    }

    public function rules(): array
    {
        return [
            'day_of_week' => [
                'sometimes',
                'integer',
                'between:0,6',
                Rule::unique('employee_weekly_schedules', 'day_of_week')
                    ->where('employee_id', $this->route('employee')?->id)
                    ->whereNull('deleted_at')
                    ->ignore($this->route('schedule')),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
