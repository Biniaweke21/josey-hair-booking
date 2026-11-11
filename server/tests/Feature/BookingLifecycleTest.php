<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Covers the new no-approval booking flow: capacity found -> booking is
 * created confirmed immediately, with no pending/approval step in between.
 */
class BookingLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_booking_is_created_confirmed_with_no_approval_step(): void
    {
        $date = Carbon::now()->next(Carbon::MONDAY)->toDateString();

        $employee = Employee::create(['name' => 'Staff One', 'employee_code' => 'BLT1', 'is_active' => true]);
        EmployeeWeeklySchedule::create(['employee_id' => $employee->id, 'day_of_week' => 1, 'is_active' => true]);

        $category = Category::create(['name' => 'Cuts', 'slug' => 'cuts-blt', 'is_active' => true, 'sort_order' => 1]);
        $service = Service::create([
            'category_id' => $category->id, 'name' => 'Wash', 'slug' => 'wash-blt',
            'base_price' => 20, 'base_duration_minutes' => 30, 'is_active' => true, 'sort_order' => 1,
        ]);

        $response = $this->postJson('/api/booking', [
            'customer' => ['name' => 'Jamie', 'email' => 'jamie-blt-test@example.com'],
            'booking_date' => $date,
            'start_time' => '10:00',
            'services' => [['service_id' => $service->id]],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', 'confirmed');
        $response->assertJson(['message' => 'Booking confirmed.']);

        $booking = Booking::where('customer_id', $response->json('data.customer.id'))->firstOrFail();
        $this->assertSame('confirmed', $booking->status);
        $this->assertNotNull($booking->confirmed_at);
    }

    public function test_admin_can_no_longer_move_a_booking_to_confirmed_via_status_endpoint(): void
    {
        $user = User::create([
            'name' => 'Lifecycle Test Admin', 'email' => 'lifecycle-test-admin@example.com',
            'password' => bcrypt('password'), 'is_active' => true,
        ]);
        $user->givePermissionTo('bookings.update');
        Sanctum::actingAs($user, ['*']);

        $customer = \App\Models\Customer::create(['name' => 'Riley', 'email' => 'riley-blt-test@example.com', 'is_active' => true]);
        $booking = Booking::create([
            'customer_id' => $customer->id, 'status' => 'confirmed', 'confirmed_at' => now(),
            'booking_date' => Carbon::now()->next(Carbon::MONDAY)->toDateString(),
            'start_time' => '10:00:00', 'end_time' => '10:30:00',
            'subtotal' => 20, 'addon_total' => 0, 'total_price' => 20, 'total_duration_minutes' => 30, 'source' => 'website',
        ]);

        $response = $this->patchJson("/api/bookings/{$booking->id}/status", ['status' => 'confirmed']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('status');
    }
}
