<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemVariationOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variation_option_id' => $this->variation_option_id,
            'variation_type_name' => $this->variation_type_name_snapshot,
            'name' => $this->name_snapshot,
            'price_adjustment' => $this->price_adjustment_snapshot,
            'duration_adjustment_minutes' => $this->duration_adjustment_snapshot,
        ];
    }
}
