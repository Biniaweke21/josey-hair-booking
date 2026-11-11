<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EmployeeLeave\StoreLeaveRequest;
use App\Http\Requests\EmployeeLeave\UpdateLeaveRequest;
use App\Http\Resources\EmployeeLeaveResource;
use App\Models\Employee;
use App\Models\EmployeeLeave;
use Illuminate\Http\JsonResponse;

class EmployeeLeaveController extends Controller
{
    public function index(Employee $employee): JsonResponse
    {
        $this->authorize('employees.view');

        $leaves = $employee->leaves()->orderByDesc('start_date')->get();

        return response()->json([
            'data' => EmployeeLeaveResource::collection($leaves),
        ]);
    }

    public function store(StoreLeaveRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('employees.update');

        $leave = $employee->leaves()->create([
            ...$request->validated(),
            'status' => $request->input('status', 'approved'),
        ]);

        return response()->json([
            'message' => 'Leave recorded successfully.',
            'data' => new EmployeeLeaveResource($leave),
        ], 201);
    }

    public function update(UpdateLeaveRequest $request, Employee $employee, EmployeeLeave $leave): JsonResponse
    {
        $this->authorize('employees.update');

        $leave->update($request->validated());

        return response()->json([
            'message' => 'Leave updated successfully.',
            'data' => new EmployeeLeaveResource($leave->fresh()),
        ]);
    }

    public function destroy(Employee $employee, EmployeeLeave $leave): JsonResponse
    {
        $this->authorize('employees.update');

        $leave->delete();

        return response()->json([
            'message' => 'Leave deleted successfully.',
        ]);
    }
}
