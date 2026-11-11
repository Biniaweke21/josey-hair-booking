<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeSchedule\StoreScheduleRequest;
use App\Http\Requests\EmployeeSchedule\UpdateScheduleRequest;
use App\Http\Resources\EmployeeWeeklyScheduleResource;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use Illuminate\Http\JsonResponse;

class EmployeeWeeklyScheduleController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        $this->authorize('employees.view');

        $schedules = $employee->weeklySchedules()->orderBy('day_of_week')->get();

        return response()->json([
            'data' => EmployeeWeeklyScheduleResource::collection($schedules),
        ]);
    }

    public function store(StoreScheduleRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('employees.update');

        $isActive = $request->boolean('is_active', true);

        // A day previously removed from the roster (soft-deleted) can be
        // re-added rather than colliding with the unique constraint.
        $schedule = EmployeeWeeklySchedule::withTrashed()
            ->where('employee_id', $employee->id)
            ->where('day_of_week', $request->input('day_of_week'))
            ->first();

        if ($schedule) {
            $schedule->restore();
            $schedule->update(['is_active' => $isActive]);
        } else {
            $schedule = $employee->weeklySchedules()->create([
                'day_of_week' => $request->input('day_of_week'),
                'is_active' => $isActive,
            ]);
        }

        return response()->json([
            'message' => 'Employee added to the roster for this day.',
            'data' => new EmployeeWeeklyScheduleResource($schedule),
        ], 201);
    }

    public function update(UpdateScheduleRequest $request, Employee $employee, EmployeeWeeklySchedule $schedule): JsonResponse
    {
        $this->authorize('employees.update');

        $schedule->update($request->validated());

        return response()->json([
            'message' => 'Schedule entry updated successfully.',
            'data' => new EmployeeWeeklyScheduleResource($schedule->fresh()),
        ]);
    }

    public function destroy(Employee $employee, EmployeeWeeklySchedule $schedule): JsonResponse
    {
        $this->authorize('employees.update');

        $schedule->delete();

        return response()->json([
            'message' => 'Employee removed from the roster for this day.',
        ]);
    }
}
