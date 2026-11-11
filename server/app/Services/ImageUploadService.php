<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageUploadService
{
    /**
     * Store a new file, deleting whatever previously occupied that slot.
     */
    public function replace(?string $oldPath, UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        if ($oldPath && Storage::disk($disk)->exists($oldPath)) {
            Storage::disk($disk)->delete($oldPath);
        }

        return $file->store($directory, $disk);
    }

    public function url(?string $path, string $disk = 'public'): ?string
    {
        return $path ? Storage::disk($disk)->url($path) : null;
    }
}
