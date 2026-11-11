<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only: customer records are created implicitly the moment someone
 * books (see BookingService::resolveCustomer), never through their own
 * form. This controller exists so admins can look them up and review
 * booking history, not to manage a separate customer-creation workflow.
 */
class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('customers.view');

        $customers = Customer::withCount('bookings')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();
                $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('customers.view');

        return response()->json([
            'data' => new CustomerResource($customer->load(['bookings' => fn ($query) => $query->orderByDesc('booking_date')->orderByDesc('start_time')])),
        ]);
    }
}
