<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'booking_date' => $this->booking_date?->toDateString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'subtotal' => $this->subtotal,
            'addon_total' => $this->addon_total,
            'total_price' => $this->total_price,
            'total_duration_minutes' => $this->total_duration_minutes,
            'notes' => $this->notes,
            'source' => $this->source,
            'customer' => $this->whenLoaded('customer', fn () => [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
            ]),
            'employees' => EmployeeResource::collection($this->whenLoaded('employees')),
            'items' => BookingItemResource::collection($this->whenLoaded('items')),
            'confirmed_at' => $this->confirmed_at,
            'checked_in_at' => $this->checked_in_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancel_reason' => $this->cancel_reason,
            'created_at' => $this->created_at,
        ];
    }
}
