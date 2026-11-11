<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'description' => $this->description,
            'meta' => $this->meta,
            'caused_by' => $this->whenLoaded('causedBy', fn () => $this->causedBy?->name),
            'created_at' => $this->created_at,
        ];
    }
}
