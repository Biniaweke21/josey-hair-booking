<?php

namespace App\Support;

trait ResolvesImageUrl
{
    /**
     * image_path is normally a relative path on the public disk, but demo/
     * seeded content may point straight at an external URL - pass those
     * through unchanged instead of mangling them with the storage prefix.
     */
    protected function resolveImageUrl(?string $path): ?string
    {
        return ImageUrl::for($path);
    }
}
