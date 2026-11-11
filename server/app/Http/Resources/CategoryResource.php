<?php

namespace App\Http\Resources;

use App\Support\ResolvesImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    use ResolvesImageUrl;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->resolveImageUrl($this->image_path),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'services_count' => $this->whenCounted('services'),
            'created_at' => $this->created_at,
        ];
    }
}
