<?php

namespace App\Support;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $scope  Extra equality constraints, for slugs that are only unique within a scope (e.g. per variation type).
     */
    protected function uniqueSlug(string $modelClass, string $name, ?int $ignoreId = null, array $scope = []): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (
            $modelClass::withTrashed()
                ->where('slug', $slug)
                ->where($scope)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-".$suffix++;
        }

        return $slug;
    }
}
