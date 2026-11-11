<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings.all';

    public function __construct(private readonly ImageUploadService $images) {}

    public function all(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Setting::all());
    }

    /**
     * All settings, cast to their real type and grouped for an admin settings screen.
     */
    public function grouped(): Collection
    {
        return $this->all()
            ->map(fn (Setting $setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $this->cast($setting),
                'type' => $setting->type,
                'group' => $setting->group,
            ])
            ->groupBy('group');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->all()->firstWhere('key', $key);

        return $setting ? $this->cast($setting) : $default;
    }

    /**
     * @param  array<int, array{key: string, value: mixed}>  $pairs
     */
    public function updateMany(array $pairs): Collection
    {
        foreach ($pairs as $pair) {
            $setting = Setting::where('key', $pair['key'])->first();

            if (! $setting) {
                continue;
            }

            $setting->update(['value' => $this->stringify($pair['value'], $setting->type)]);
        }

        Cache::forget(self::CACHE_KEY);

        return $this->all();
    }

    /**
     * Store a new logo, replacing and deleting whatever was there before.
     */
    public function uploadLogo(UploadedFile $file): string
    {
        $existing = Setting::where('key', 'salon_logo_path')->first();

        $path = $this->images->replace($existing?->value, $file, 'salon');

        Setting::updateOrCreate(
            ['key' => 'salon_logo_path'],
            ['value' => $path, 'type' => 'string', 'group' => 'general'],
        );

        Cache::forget(self::CACHE_KEY);

        return $path;
    }

    private function cast(Setting $setting): mixed
    {
        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'number' => str_contains((string) $setting->value, '.') ? (float) $setting->value : (int) $setting->value,
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }

    private function stringify(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
