<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'name' => $this->name_snapshot,
            'category' => $this->whenLoaded('service', fn () => $this->service?->category?->name),
            'price' => $this->price_snapshot,
            'duration_minutes' => $this->duration_snapshot,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'variation_options' => BookingItemVariationOptionResource::collection($this->whenLoaded('variationOptions')),
            'add_ons' => BookingItemAddOnResource::collection($this->whenLoaded('addOns')),
        ];
    }
}
