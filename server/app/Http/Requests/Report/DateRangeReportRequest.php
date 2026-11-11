<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class DateRangeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reports.view');
    }

    public function rules(): array
    {
        return [
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:from'],
            'group_by' => ['sometimes', Rule::in(['day', 'week', 'month'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function from(): string
    {
        return $this->input('from') ?? Carbon::today()->subDays(29)->toDateString();
    }

    public function to(): string
    {
        return $this->input('to') ?? Carbon::today()->toDateString();
    }

    public function groupBy(): string
    {
        return $this->input('group_by', 'day');
    }

    public function limitOrDefault(int $default = 5): int
    {
        return (int) $this->input('limit', $default);
    }
}
