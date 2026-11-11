<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Booking counts per period (day/week/month), broken down by status.
     * Bucket boundaries are computed in SQL, never hardcoded.
     *
     * @return array<int, array<string, mixed>>
     */
    public function bookingsTimeSeries(string $from, string $to, string $groupBy = 'day'): array
    {
        $period = $this->periodExpression($groupBy);

        $rows = DB::table('bookings')
            ->whereBetween('booking_date', [$from, $to])
            ->select(DB::raw("{$period} as period"), 'status', DB::raw('count(*) as count'))
            ->groupBy('period', 'status')
            ->orderBy('period')
            ->get();

        return $rows->groupBy('period')->map(function ($rows, $period) {
            $byStatus = $rows->pluck('count', 'status');

            return [
                'period' => $period,
                'total' => (int) $rows->sum('count'),
                'confirmed' => (int) ($byStatus['confirmed'] ?? 0),
                'checked_in' => (int) ($byStatus['checked_in'] ?? 0),
                'completed' => (int) ($byStatus['completed'] ?? 0),
                'cancelled' => (int) ($byStatus['cancelled'] ?? 0),
                'no_show' => (int) ($byStatus['no_show'] ?? 0),
            ];
        })->values()->all();
    }

    /**
     * Realized revenue (completed bookings only) per period.
     *
     * @return array<int, array{period: string, revenue: float, bookings_count: int}>
     */
    public function revenueTimeSeries(string $from, string $to, string $groupBy = 'day'): array
    {
        $period = $this->periodExpression($groupBy);

        return DB::table('bookings')
            ->whereBetween('booking_date', [$from, $to])
            ->where('status', 'completed')
            ->select(DB::raw("{$period} as period"), DB::raw('sum(total_price) as revenue'), DB::raw('count(*) as bookings_count'))
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->map(fn ($row) => [
                'period' => $row->period,
                'revenue' => (float) $row->revenue,
                'bookings_count' => (int) $row->bookings_count,
            ])
            ->all();
    }

    /**
     * @return array<int, array{service_id: int, service_name: string, bookings_count: int, revenue: float}>
     */
    public function popularServices(string $from, string $to, int $limit = 5): array
    {
        return DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->join('services', 'services.id', '=', 'booking_items.service_id')
            ->whereBetween('bookings.booking_date', [$from, $to])
            ->where('bookings.status', '!=', 'cancelled')
            ->select(
                'services.id as service_id',
                'services.name as service_name',
                DB::raw('count(*) as bookings_count'),
                DB::raw('sum(booking_items.price_snapshot) as revenue'),
            )
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('bookings_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'service_id' => $row->service_id,
                'service_name' => $row->service_name,
                'bookings_count' => (int) $row->bookings_count,
                'revenue' => (float) $row->revenue,
            ])
            ->all();
    }

    /**
     * Workload is counted per assigned service line, not per booking - an
     * employee assigned to one of three services on a booking only did
     * that one service, not the whole appointment.
     *
     * @return array<int, array{employee_id: int, employee_name: string, services_count: int, total_hours: float}>
     */
    public function employeeWorkload(string $from, string $to): array
    {
        return DB::table('booking_items')
            ->join('bookings', 'bookings.id', '=', 'booking_items.booking_id')
            ->join('employees', 'employees.id', '=', 'booking_items.employee_id')
            ->whereBetween('bookings.booking_date', [$from, $to])
            ->where('bookings.status', '!=', 'cancelled')
            ->select(
                'employees.id as employee_id',
                'employees.name as employee_name',
                DB::raw('count(*) as services_count'),
                DB::raw('sum(booking_items.duration_snapshot) as total_minutes'),
            )
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('services_count')
            ->get()
            ->map(fn ($row) => [
                'employee_id' => $row->employee_id,
                'employee_name' => $row->employee_name,
                'services_count' => (int) $row->services_count,
                'total_hours' => round($row->total_minutes / 60, 1),
            ])
            ->all();
    }

    /**
     * @return array<int, array{customer_id: int, customer_name: string, bookings_count: int, total_spent: float}>
     */
    public function topCustomers(string $from, string $to, int $limit = 5): array
    {
        return DB::table('bookings')
            ->join('customers', 'customers.id', '=', 'bookings.customer_id')
            ->whereBetween('bookings.booking_date', [$from, $to])
            ->where('bookings.status', 'completed')
            ->select(
                'customers.id as customer_id',
                'customers.name as customer_name',
                DB::raw('count(*) as bookings_count'),
                DB::raw('sum(bookings.total_price) as total_spent'),
            )
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total_spent')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'customer_id' => $row->customer_id,
                'customer_name' => $row->customer_name,
                'bookings_count' => (int) $row->bookings_count,
                'total_spent' => (float) $row->total_spent,
            ])
            ->all();
    }

    private function periodExpression(string $groupBy): string
    {
        return match ($groupBy) {
            'month' => "DATE_FORMAT(booking_date, '%Y-%m-01')",
            'week' => 'DATE_SUB(booking_date, INTERVAL WEEKDAY(booking_date) DAY)',
            default => 'booking_date',
        };
    }
}
