<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ImageUrl
{
    /**
     * Browser-ready URL for an image on the "public" disk.
     *
     * External URLs (demo/seeded content) pass through unchanged. When the disk is
     * a private S3-compatible bucket, return a signed link instead of a plain one.
     */
    public static function for(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $disk = Storage::disk('public');

        return config('filesystems.disks.public.driver') === 's3'
            ? $disk->temporaryUrl($path, now()->addDays(6))
            : $disk->url($path);
    }
}
