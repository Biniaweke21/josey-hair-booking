<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingItemAddOnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'add_on_id' => $this->add_on_id,
            'name' => $this->name_snapshot,
            'price' => $this->price_snapshot,
            'duration_minutes' => $this->duration_snapshot,
        ];
    }
}
