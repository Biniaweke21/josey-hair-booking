<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CapacityDashboardTest extends TestCase
{
    use DatabaseTransactions;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();

        // Any Monday works: the dashboard doesn't care whether the date is
        // bookable, only what's rostered and what's already booked on it.
        $this->date = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $user = User::create([
            'name' => 'Capacity Test Admin',
            'email' => 'capacity-test-admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $user->givePermissionTo('reports.view');
        Sanctum::actingAs($user, ['*']);

        // 3 employees rostered Monday = capacity 3.
        foreach (['TDT1', 'TDT2', 'TDT3'] as $code) {
            $employee = Employee::create(['name' => "Staff {$code}", 'employee_code' => $code, 'is_active' => true]);
            EmployeeWeeklySchedule::create(['employee_id' => $employee->id, 'day_of_week' => 1, 'is_active' => true]);
        }

        $customer = Customer::create(['name' => 'Emily', 'email' => 'emily-capacity-test@example.com', 'is_active' => true]);

        // A: confirmed, 09:00-14:00 (5h) - active all day. Bookings are
        // created confirmed directly now, no pending approval step exists.
        Booking::create([
            'customer_id' => $customer->id, 'status' => 'confirmed',
            'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '14:00:00',
            'subtotal' => 100, 'addon_total' => 0, 'total_price' => 100, 'total_duration_minutes' => 300, 'source' => 'website',
        ]);

        // B: confirmed, 09:30-11:30 (2h) - overlaps A.
        Booking::create([
            'customer_id' => $customer->id, 'status' => 'confirmed',
            'booking_date' => $this->date, 'start_time' => '09:30:00', 'end_time' => '11:30:00',
            'subtotal' => 50, 'addon_total' => 0, 'total_price' => 50, 'total_duration_minutes' => 120, 'source' => 'website',
        ]);

        // C: confirmed, 09:15-10:00 (45m) - overlaps both, pushes 09:30 to full.
        Booking::create([
            'customer_id' => $customer->id, 'status' => 'confirmed',
            'booking_date' => $this->date, 'start_time' => '09:15:00', 'end_time' => '10:00:00',
            'subtotal' => 30, 'addon_total' => 0, 'total_price' => 30, 'total_duration_minutes' => 45, 'source' => 'website',
        ]);

        // D: cancelled, 09:00-10:00 - must never count toward reserved capacity.
        Booking::create([
            'customer_id' => $customer->id, 'status' => 'cancelled',
            'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '10:00:00',
            'subtotal' => 50, 'addon_total' => 0, 'total_price' => 50, 'total_duration_minutes' => 60, 'source' => 'website',
            'cancelled_at' => now(), 'cancel_reason' => 'test',
        ]);

        // E: pending, 09:00-09:15 - the approval workflow is gone, but the
        // status still exists in the DB enum for legacy rows. Must never
        // reserve capacity, same as cancelled/completed/no_show.
        Booking::create([
            'customer_id' => $customer->id, 'status' => 'pending',
            'booking_date' => $this->date, 'start_time' => '09:00:00', 'end_time' => '09:15:00',
            'subtotal' => 10, 'addon_total' => 0, 'total_price' => 10, 'total_duration_minutes' => 15, 'source' => 'website',
        ]);
    }

    public function test_summary_reports_peak_reserved_and_excludes_cancelled_bookings(): void
    {
        $response = $this->getJson("/api/capacity/{$this->date}/summary");

        $response->assertOk()->assertJson([
            'data' => [
                'date' => $this->date,
                'working_staff' => 3,
                'capacity' => 3,
                'reserved' => 3,
                'remaining' => 0,
                'total_bookings' => 3,
            ],
        ]);
    }

    public function test_timeline_marks_full_limited_and_available_correctly(): void
    {
        $response = $this->getJson("/api/capacity/{$this->date}/timeline");
        $response->assertOk();

        $byTime = collect($response->json('data'))->keyBy('time');

        $this->assertSame(['reserved' => 1, 'remaining' => 2, 'status' => 'Limited'], \Illuminate\Support\Arr::only($byTime['09:00'], ['reserved', 'remaining', 'status']));
        $this->assertSame(['reserved' => 3, 'remaining' => 0, 'status' => 'Full'], \Illuminate\Support\Arr::only($byTime['09:30'], ['reserved', 'remaining', 'status']));
        $this->assertSame(['reserved' => 2, 'remaining' => 1, 'status' => 'Limited'], \Illuminate\Support\Arr::only($byTime['10:00'], ['reserved', 'remaining', 'status']));
        $this->assertSame(['reserved' => 0, 'remaining' => 3, 'status' => 'Available'], \Illuminate\Support\Arr::only($byTime['14:00'], ['reserved', 'remaining', 'status']));
    }

    public function test_booking_details_at_interval_excludes_cancelled_and_pending(): void
    {
        // At 09:00: A (confirmed) is active. D (cancelled) and E (pending)
        // also cover this minute but must never reserve capacity or appear.
        $response = $this->getJson("/api/capacity/{$this->date}/bookings?time=09:00");
        $response->assertOk();

        $statuses = collect($response->json('data'))->pluck('status')->all();

        $this->assertSame(['confirmed'], $statuses);
    }

    public function test_analytics_reports_peak_and_max_concurrent(): void
    {
        $response = $this->getJson("/api/capacity/{$this->date}/analytics");
        $response->assertOk();

        $response->assertJsonPath('data.max_concurrent_bookings', 3);
        $response->assertJsonPath('data.peak_hour', '09:00');
    }

    public function test_invalid_calendar_date_is_rejected(): void
    {
        $response = $this->getJson('/api/capacity/2026-13-45/summary');

        $response->assertStatus(422);
    }
}
