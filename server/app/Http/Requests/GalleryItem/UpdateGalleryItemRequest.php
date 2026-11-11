<?php

namespace App\Http\Requests\GalleryItem;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGalleryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gallery.update');
    }

    public function rules(): array
    {
        return [
            'image' => ['sometimes', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'title' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
