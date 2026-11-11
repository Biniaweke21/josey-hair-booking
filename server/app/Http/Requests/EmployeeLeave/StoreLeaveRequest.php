<?php

namespace App\Http\Requests\EmployeeLeave;

use App\Models\EmployeeLeave;
use App\Support\ChecksForOverlap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeaveRequest extends FormRequest
{
    use ChecksForOverlap;

    public function authorize(): bool
    {
        return $this->user()->can('employees.update');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['sick', 'vacation', 'personal', 'other'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['pending', 'approved', 'rejected'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employee = $this->route('employee');

            if (! $employee || $validator->errors()->isNotEmpty()) {
                return;
            }

            $overlap = $this->overlaps(
                EmployeeLeave::class,
                ['employee_id' => $employee->id],
                'start_date',
                $this->input('start_date'),
                'end_date',
                $this->input('end_date'),
                inclusive: true,
            );

            if ($overlap) {
                $validator->errors()->add('start_date', 'This overlaps another leave already recorded for this employee.');
            }
        });
    }
}
