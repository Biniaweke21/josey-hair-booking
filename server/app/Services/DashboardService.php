<?php

namespace App\Services;

use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private readonly ReportService $reports) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        $last30Start = $today->copy()->subDays(29);

        return [
            'today' => [
                'counts' => $this->bookingCountsForDate($today->toDateString()),
                'bookings' => BookingResource::collection(
                    Booking::where('booking_date', $today->toDateString())
                        ->with(['customer', 'employees', 'items.service.category'])
                        ->orderBy('start_time')
                        ->get(),
                ),
            ],
            'upcoming' => [
                'count' => Booking::where('booking_date', '>', $today->toDateString())
                    ->where('status', 'confirmed')
                    ->count(),
                'next' => BookingResource::collection(
                    Booking::where('booking_date', '>', $today->toDateString())
                        ->where('status', 'confirmed')
                        ->with(['customer', 'employees', 'items'])
                        ->orderBy('booking_date')
                        ->orderBy('start_time')
                        ->limit(5)
                        ->get(),
                ),
            ],
            'completed_count' => Booking::where('status', 'completed')->count(),
            'cancelled_count' => Booking::where('status', 'cancelled')->count(),
            'no_show_count' => Booking::where('status', 'no_show')->count(),
            'customers' => [
                'total' => Customer::count(),
                'new_this_month' => Customer::where('created_at', '>=', $monthStart)->count(),
            ],
            'revenue' => [
                'today' => $this->realizedRevenue($today->toDateString(), $today->toDateString()),
                'this_week' => $this->realizedRevenue($weekStart->toDateString(), $weekEnd->toDateString()),
                'this_month' => $this->realizedRevenue($monthStart->toDateString(), $monthEnd->toDateString()),
                'all_time' => $this->realizedRevenue(null, null),
                'projected' => (float) Booking::whereIn('status', Booking::ACTIVE_STATUSES)->sum('total_price'),
            ],
            'popular_services' => $this->reports->popularServices($last30Start->toDateString(), $today->toDateString(), 5),
            'employee_workload' => $this->reports->employeeWorkload($weekStart->toDateString(), $weekEnd->toDateString()),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function bookingCountsForDate(string $date): array
    {
        $byStatus = DB::table('bookings')
            ->where('booking_date', $date)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'total' => (int) $byStatus->sum(),
            'confirmed' => (int) ($byStatus['confirmed'] ?? 0),
            'checked_in' => (int) ($byStatus['checked_in'] ?? 0),
            'completed' => (int) ($byStatus['completed'] ?? 0),
            'cancelled' => (int) ($byStatus['cancelled'] ?? 0),
            'no_show' => (int) ($byStatus['no_show'] ?? 0),
        ];
    }

    private function realizedRevenue(?string $from, ?string $to): float
    {
        return (float) Booking::where('status', 'completed')
            ->when($from && $to, fn ($query) => $query->whereBetween('booking_date', [$from, $to]))
            ->sum('total_price');
    }
}
