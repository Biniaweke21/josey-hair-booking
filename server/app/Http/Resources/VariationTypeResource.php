<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'attached_sort_order' => $this->whenPivotLoaded('service_variation_types', fn () => $this->pivot->sort_order),
            'options' => VariationOptionResource::collection($this->whenLoaded('options')),
            'created_at' => $this->created_at,
        ];
    }
}
