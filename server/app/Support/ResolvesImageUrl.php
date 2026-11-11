<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

trait ResolvesImageUrl
{
    /**
     * image_path is normally a relative path on the public disk, but demo/
     * seeded content may point straight at an external URL - pass those
     * through unchanged instead of mangling them with the storage prefix.
     */
    protected function resolveImageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
