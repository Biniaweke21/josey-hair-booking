<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use App\Models\GalleryItem;
use App\Models\Service;
use App\Models\ServiceVariationOption;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Realistic demo catalog for Beauty By Josey (hair salon). NOT part of the
 * default DatabaseSeeder — run manually with:
 *   php artisan db:seed --class=DemoDataSeeder
 * Safe to re-run (uses firstOrCreate on natural keys).
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['Braids & Twists', 'Protective braided styles, from box braids to cornrows.'],
            ['Wash & Style', 'Wash, blow-dry, silk press and everyday styling.'],
            ['Color', 'Full color, highlights, balayage and gloss treatments.'],
            ['Treatments', 'Deep conditioning, scalp and repair treatments.'],
            ['Extensions', 'Sew-ins, wig installs and tape-in extensions.'],
        ];

        $categoryModels = [];
        foreach ($categories as $i => [$name, $desc]) {
            $categoryModels[$name] = Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'description' => $desc, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }

        // Variation type: Hair Length, reused across services with per-service pricing.
        $lengthType = VariationType::firstOrCreate(
            ['slug' => 'hair-length'],
            ['name' => 'Hair Length', 'sort_order' => 1, 'is_active' => true],
        );
        $lengths = [];
        foreach (['Short', 'Medium', 'Long', 'Extra Long'] as $i => $label) {
            $lengths[$label] = VariationOption::firstOrCreate(
                ['variation_type_id' => $lengthType->id, 'slug' => Str::slug($label)],
                ['name' => $label, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }

        // Global add-ons.
        $addOns = [];
        foreach ([
            ['Deep Conditioning', 25, 20],
            ['Scalp Treatment', 20, 15],
            ['Hair Gloss', 35, 30],
            ['Trim', 15, 15],
        ] as $i => [$name, $price, $mins]) {
            $addOns[$name] = AddOn::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'price_modifier' => $price, 'duration_modifier_minutes' => $mins, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }

        // Services per category: [name, price, minutes, description, usesLength, addOnNames]
        $services = [
            'Braids & Twists' => [
                ['Box Braids', 160, 300, 'Classic box braids in your chosen length.', true, ['Trim', 'Scalp Treatment']],
                ['Knotless Braids', 190, 330, 'Lightweight knotless braids, gentle on the scalp.', true, ['Scalp Treatment']],
                ['Cornrows', 90, 150, 'Neat straight-back or design cornrows.', true, ['Trim']],
            ],
            'Wash & Style' => [
                ['Wash & Silk Press', 75, 90, 'Cleanse, blow-dry and a sleek silk press.', true, ['Deep Conditioning', 'Trim']],
                ['Wash & Go', 45, 60, 'Refreshing wash and natural style.', false, ['Deep Conditioning']],
            ],
            'Color' => [
                ['Full Color', 120, 150, 'All-over permanent color.', true, ['Hair Gloss', 'Deep Conditioning']],
                ['Balayage', 180, 210, 'Hand-painted, sun-kissed dimension.', true, ['Hair Gloss']],
            ],
            'Treatments' => [
                ['Deep Repair Treatment', 55, 60, 'Intensive bond-repair treatment.', false, ['Scalp Treatment']],
            ],
            'Extensions' => [
                ['Sew-In Install', 220, 240, 'Full sew-in with leave-out or closure.', true, ['Trim', 'Deep Conditioning']],
                ['Wig Install', 130, 120, 'Custom wig install and styling.', false, ['Hair Gloss']],
            ],
        ];

        $serviceImage = 0;
        foreach ($services as $categoryName => $rows) {
            foreach ($rows as [$name, $price, $mins, $desc, $usesLength, $addOnNames]) {
                $serviceImage++;
                $service = Service::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'category_id' => $categoryModels[$categoryName]->id,
                        'name' => $name,
                        'description' => $desc,
                        'base_price' => $price,
                        'base_duration_minutes' => $mins,
                        'image_path' => sprintf('gallery/design-%02d.jpg', $serviceImage),
                        'is_active' => true,
                    ],
                );

                if ($usesLength) {
                    $service->variationTypes()->syncWithoutDetaching([$lengthType->id => ['sort_order' => 1]]);
                    // Longer hair costs more / takes longer.
                    $adjustments = [
                        'Short' => [0, 0],
                        'Medium' => [20, 30],
                        'Long' => [45, 60],
                        'Extra Long' => [75, 90],
                    ];
                    foreach ($adjustments as $label => [$dp, $dm]) {
                        ServiceVariationOption::firstOrCreate(
                            ['service_id' => $service->id, 'variation_option_id' => $lengths[$label]->id],
                            ['price_adjustment' => $dp, 'duration_adjustment_minutes' => $dm, 'is_active' => true],
                        );
                    }
                }

                $addOnIds = collect($addOnNames)->map(fn ($n) => $addOns[$n]->id)->all();
                $service->addOns()->syncWithoutDetaching($addOnIds);
            }
        }

        // Gallery: reuse the design images copied into storage/app/public/gallery.
        $galleryCats = array_values($categoryModels);
        for ($n = 1; $n <= 12; $n++) {
            $file = sprintf('gallery/design-%02d.jpg', $n);
            GalleryItem::firstOrCreate(
                ['image_path' => $file],
                [
                    'category_id' => $galleryCats[($n - 1) % count($galleryCats)]->id,
                    'title' => 'Style '.$n,
                    'sort_order' => $n,
                    'is_active' => true,
                ],
            );
        }

        // Employees + weekly roster (drives booking capacity).
        $staff = ['Josey Bekele', 'Aisha Bright', 'Maya Coleman', 'Nia Thompson', 'Ruth Alvarez'];
        foreach ($staff as $i => $name) {
            $employee = Employee::firstOrCreate(
                ['employee_code' => 'EMP'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                ['name' => $name, 'is_active' => true, 'hired_at' => now()->subMonths(6 + $i)],
            );
            // Work Tuesday–Saturday (2..6).
            foreach ([2, 3, 4, 5, 6] as $dow) {
                EmployeeWeeklySchedule::firstOrCreate(
                    ['employee_id' => $employee->id, 'day_of_week' => $dow],
                    ['is_active' => true],
                );
            }
        }
    }
}
