<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CapacityBookingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer->name),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'duration_minutes' => $this->total_duration_minutes,
            'duration_label' => $this->durationLabel($this->total_duration_minutes),
            'status' => $this->status,
        ];
    }

    private function durationLabel(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        if ($hours > 0 && $remainder > 0) {
            return "{$hours}h {$remainder}m";
        }

        if ($hours > 0) {
            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        return "{$remainder} min";
    }
}
