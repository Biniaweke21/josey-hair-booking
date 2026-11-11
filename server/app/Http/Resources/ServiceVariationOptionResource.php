<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceVariationOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'variation_option' => new VariationOptionResource($this->whenLoaded('variationOption')),
            'price_adjustment' => $this->price_adjustment,
            'duration_adjustment_minutes' => $this->duration_adjustment_minutes,
            'is_active' => $this->is_active,
        ];
    }
}
