<?php

namespace App\Support;

trait ChecksForOverlap
{
    /**
     * True if any existing row matching $scope has a range that overlaps
     * the given range.
     *
     * Use $inclusive = false (default) for half-open time-of-day ranges
     * where touching endpoints don't count as overlapping (a shift ending
     * at 18:00 doesn't overlap one starting at 18:00). Use $inclusive =
     * true for whole-day date ranges, where both endpoints are part of
     * the range (Dec 20-22 and Dec 22-24 do overlap, on the 22nd).
     *
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $scope
     */
    protected function overlaps(
        string $modelClass,
        array $scope,
        string $startColumn,
        string $startValue,
        string $endColumn,
        string $endValue,
        ?int $ignoreId = null,
        bool $inclusive = false,
    ): bool {
        return $modelClass::where($scope)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where($startColumn, $inclusive ? '<=' : '<', $endValue)
            ->where($endColumn, $inclusive ? '>=' : '>', $startValue)
            ->exists();
    }
}
