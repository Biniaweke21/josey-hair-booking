<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Report\DateRangeReportRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function bookingsTimeSeries(DateRangeReportRequest $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $this->reports->bookingsTimeSeries($request->from(), $request->to(), $request->groupBy()),
        ]);
    }

    public function revenueTimeSeries(DateRangeReportRequest $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $this->reports->revenueTimeSeries($request->from(), $request->to(), $request->groupBy()),
        ]);
    }

    public function popularServices(DateRangeReportRequest $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $this->reports->popularServices($request->from(), $request->to(), $request->limitOrDefault(5)),
        ]);
    }

    public function employeeWorkload(DateRangeReportRequest $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $this->reports->employeeWorkload($request->from(), $request->to()),
        ]);
    }

    public function topCustomers(DateRangeReportRequest $request): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json([
            'data' => $this->reports->topCustomers($request->from(), $request->to(), $request->limitOrDefault(5)),
        ]);
    }
}
