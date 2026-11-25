<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\BusinessHour;
use App\Models\Category;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use App\Models\Service;
use App\Models\ServiceVariationOption;
use App\Models\VariationOption;
use App\Models\VariationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Beauty By Josey's real service menu, taken from beautybyjosey.com/our-services
 * (prices) and /booking (the few listed braid durations), November 2026.
 *
 * PRICES are hers. Where her menu says "and up", base_price is that starting
 * price and the description says so.
 *
 * DURATIONS: her site lists almost none, so most minutes below are ESTIMATES
 * for scheduling only. Josey should confirm them in Admin > Services.
 *
 * Staff: one stylist ("Josey") working every day, with the salon open on her
 * advertised hours (daily 9am-7pm), so the calendar never offers more parallel
 * slots than she can serve. Add stylists in Admin > Employees for more capacity.
 *
 * Run: php artisan db:seed --class=JoseyCatalogSeeder   (safe to re-run)
 */
class JoseyCatalogSeeder extends Seeder
{
    private const STARTING = 'Starting price; the final price depends on length, size and hair type.';

    public function run(): void
    {
        // [category => [description, [[name, price, minutes, startingPrice?, lengthOptions?], ...]]]
        $menu = [
            'Braid Styling' => ['Box braids, knotless, twists, cornrows and kids\' styles.', [
                ['Knotless Braids', 160, 300, true, true],
                ['Bohemian Box Braids', 160, 300, true, true],
                ['Individual Braids', 140, 300, true, true],
                ['Jumbo Braids', 110, 180, true, false],
                ['Jumbo Twist', 110, 180, true, false],
                ['Feed-In Braids', 65, 120, true, false],
                ['Ponytail Braid', 50, 90, false, false],
                ['Crochet', 80, 150, false, false],
                ['Double Strand Twist', 65, 180, true, false],
                ['Mohawk Braids or Twist', 50, 120, true, false],
                ['Natural Hair Individual Braids', 60, 180, true, false],
                ['Natural Hair Cornrows', 55, 90, true, false],
                ['Kids Braids (12 and under)', 50, 150, true, false],
                ['Braid Take Out', 25, 90, true, false],
            ]],
            'Hair Styling' => ['Wash, set, blow dry, silk press and styling.', [
                ['Wash Only', 15, 20, false, false],
                ['Wash and Set', 35, 60, false, false],
                ['Wash and Blow Dry', 25, 45, false, false],
                ['Roller Wrap', 35, 60, false, false],
                ['Flexi Rod Set', 65, 90, false, false],
                ['Flat Iron Style', 50, 60, true, false],
                ['Thermal Waving', 55, 60, true, false],
                ['Silk Press', 65, 90, true, false],
            ]],
            'Hair Extensions' => ['Sew-ins, wig installs, tape-ins and clip-ins.', [
                ['Sew-In (Two Bundles)', 125, 150, false, false],
                ['Sew-In (3 or More Bundles)', 150, 180, false, false],
                ['Tracks (each)', 15, 15, false, false],
                ['Clip-Ins', 15, 30, true, false],
                ['Tape Extensions', 150, 120, true, false],
                ['Human Hair Ponytail Extension', 120, 60, false, false],
                ['Frontal Wig Install', 150, 120, false, false],
                ['Hair Extension Removal', 15, 30, true, false],
                ['Hair Extension Shampoo and Set', 25, 60, false, false],
            ]],
            'Locs' => ['Starter locs, maintenance, retwists and loc styling.', [
                ['Loc Shampoo (Wash Only)', 30, 30, false, false],
                ['Loc Treatment', 55, 45, false, false],
                ['Loc Detox', 60, 60, false, false],
                ['Comb Coil Starter Locs', 150, 120, true, false],
                ['Two Strand Starter Locs', 100, 120, true, false],
                ['Mohawk Starter Locs', 60, 90, true, false],
                ['Palm Roll (Mohawk)', 62, 90, true, false],
                ['Palm Roll (Shoulder Length)', 100, 120, true, false],
                ['Palm Roll (Mid Back)', 120, 150, true, false],
                ['Interlock', 110, 180, true, false],
                ['Loc Style: Spiral', 50, 90, true, false],
                ['Loc Style: Cornrows or Braids', 55, 90, true, false],
                ['Loc Style: Two Strand', 60, 90, true, false],
                ['Loc Style: Barrel Rolls', 45, 60, true, false],
            ]],
            'Hair Treatments' => ['Conditioning and repair treatments.', [
                ['Deep Conditioner', 15, 30, false, false],
                ['Steam Conditioner Treatment', 20, 30, false, false],
                ['Hot Oil Treatment', 10, 20, false, false],
                ['Placenta Treatment', 10, 20, false, false],
                ['Hair Mask Treatment', 20, 30, false, false],
            ]],
            'Chemical Services' => ['Relaxers and texturizers.', [
                ['Virgin Hair Relaxer', 90, 120, true, false],
                ['Relaxer Retouch', 65, 90, true, false],
                ['Texturizer', 90, 120, true, false],
            ]],
            'Color Services' => ['Full color, highlights, balayage and gray coverage.', [
                ['One Color Full Head', 140, 120, true, false],
                ['Root Touch-Up', 95, 90, true, false],
                ['Full Head Highlights', 125, 150, true, false],
                ['Partial Highlights', 95, 120, true, false],
                ['Lowlights Between Color', 165, 180, true, false],
                ['Highlights and Toner', 150, 180, true, false],
                ['Balayage', 185, 210, true, false],
                ['Ombré', 125, 180, true, false],
                ['Full Head Lighten and Tone', 220, 240, true, false],
                ['Color Correction', 150, 240, true, false],
                ['Full Head Gray Coverage', 35, 60, false, false],
                ['Partial Gray Coverage', 20, 45, false, false],
                ['Gray Coverage Retouch', 15, 45, true, false],
            ]],
        ];

        // Braid lengths from her booking page. Extra time per length is an
        // ESTIMATE; she lists no per-length prices, so price adjustments are 0.
        $lengthType = VariationType::firstOrCreate(
            ['slug' => 'braid-length'],
            ['name' => 'Braid Length', 'sort_order' => 1, 'is_active' => true],
        );
        $lengths = [];
        foreach (['Mid Back' => 0, 'Lower Back' => 30, 'Waist Length' => 60, 'Butt Length' => 90, 'Hip Length' => 120] as $label => $extra) {
            $lengths[] = [VariationOption::firstOrCreate(
                ['variation_type_id' => $lengthType->id, 'slug' => Str::slug($label)],
                ['name' => $label, 'sort_order' => count($lengths) + 1, 'is_active' => true],
            ), $extra];
        }

        // Her treatments, also offered as add-ons to braids and styling.
        $addOns = [];
        foreach ([['Deep Conditioner', 15, 30], ['Steam Conditioner Treatment', 20, 30], ['Hot Oil Treatment', 10, 20]] as $i => [$name, $price, $mins]) {
            $addOns[] = AddOn::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'price_modifier' => $price, 'duration_modifier_minutes' => $mins, 'sort_order' => $i + 1, 'is_active' => true],
            );
        }

        $sort = 0;
        foreach ($menu as $categoryName => [$categoryDesc, $services]) {
            $category = Category::firstOrCreate(
                ['slug' => Str::slug($categoryName)],
                ['name' => $categoryName, 'description' => $categoryDesc, 'sort_order' => ++$sort, 'is_active' => true],
            );

            foreach ($services as $i => [$name, $price, $minutes, $starting, $usesLength]) {
                $service = Service::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'category_id' => $category->id,
                        'name' => $name,
                        'description' => $starting ? self::STARTING : null,
                        'base_price' => $price,
                        'base_duration_minutes' => $minutes,
                        'sort_order' => $i + 1,
                        'is_active' => true,
                    ],
                );

                if ($usesLength) {
                    $service->variationTypes()->syncWithoutDetaching([$lengthType->id => ['sort_order' => 1]]);
                    foreach ($lengths as [$option, $extra]) {
                        ServiceVariationOption::firstOrCreate(
                            ['service_id' => $service->id, 'variation_option_id' => $option->id],
                            ['price_adjustment' => 0, 'duration_adjustment_minutes' => $extra, 'is_active' => true],
                        );
                    }
                }

                if (in_array($categoryName, ['Braid Styling', 'Hair Styling'], true)) {
                    $service->addOns()->syncWithoutDetaching(collect($addOns)->pluck('id')->all());
                }
            }
        }

        // Her advertised hours: every day 9am-7pm.
        foreach (range(0, 6) as $day) {
            BusinessHour::updateOrCreate(['day_of_week' => $day], ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '19:00']);
        }

        // A weekly schedule only records which days she works; the hours come
        // from the business hours above.
        $josey = Employee::firstOrCreate(['name' => 'Josey'], ['is_active' => true]);
        foreach (range(0, 6) as $day) {
            EmployeeWeeklySchedule::firstOrCreate(
                ['employee_id' => $josey->id, 'day_of_week' => $day],
                ['is_active' => true],
            );
        }
    }
}
