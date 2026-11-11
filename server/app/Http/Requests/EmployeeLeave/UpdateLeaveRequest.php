<?php

namespace App\Http\Requests\EmployeeLeave;

use App\Models\EmployeeLeave;
use App\Support\ChecksForOverlap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateLeaveRequest extends FormRequest
{
    use ChecksForOverlap;

    public function authorize(): bool
    {
        return $this->user()->can('employees.update');
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['sick', 'vacation', 'personal', 'other'])],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employee = $this->route('employee');
            $leave = $this->route('leave');

            if (! $employee || ! $leave || $validator->errors()->isNotEmpty()) {
                return;
            }

            $startDate = $this->input('start_date', $leave->start_date->toDateString());
            $endDate = $this->input('end_date', $leave->end_date->toDateString());

            $overlap = $this->overlaps(
                EmployeeLeave::class,
                ['employee_id' => $employee->id],
                'start_date',
                $startDate,
                'end_date',
                $endDate,
                ignoreId: $leave->id,
                inclusive: true,
            );

            if ($overlap) {
                $validator->errors()->add('start_date', 'This overlaps another leave already recorded for this employee.');
            }
        });
    }
}
