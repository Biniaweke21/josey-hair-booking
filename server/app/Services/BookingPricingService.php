<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceVariationOption;
use Illuminate\Validation\ValidationException;

class BookingPricingService
{
    public function __construct(private readonly SettingService $settings) {}

    /**
     * Validates every selected service/variation/add-on is actually offered,
     * and computes the priced, snapshotted breakdown for a booking.
     *
     * @param  array<int, array{service_id: int, variation_option_ids?: array<int, int>, add_on_ids?: array<int, int>}>  $items
     * @return array{items: array, subtotal: float, addon_total: float, total_price: float, total_duration_minutes: int}
     */
    public function price(array $items): array
    {
        $maxServices = (int) $this->settings->get('booking_max_services_per_booking', 0);

        if ($maxServices > 0 && count($items) > $maxServices) {
            throw ValidationException::withMessages([
                'services' => ["A booking can include at most {$maxServices} service(s)."],
            ]);
        }

        $builtItems = [];
        $subtotal = 0.0;
        $addonTotal = 0.0;
        $totalDuration = 0;

        foreach ($items as $itemInput) {
            $service = Service::where('is_active', true)->find($itemInput['service_id']);

            if (! $service) {
                throw ValidationException::withMessages([
                    'services' => ["Service #{$itemInput['service_id']} is not available."],
                ]);
            }

            $price = (float) $service->base_price;
            $duration = (int) $service->base_duration_minutes;

            [$variationOptions, $price, $duration] = $this->applyVariations($service, $itemInput['variation_option_ids'] ?? [], $price, $duration);
            [$addOns, $addOnPrice, $addOnDuration] = $this->applyAddOns($service, $itemInput['add_on_ids'] ?? []);

            $builtItems[] = [
                'service_id' => $service->id,
                'name_snapshot' => $service->name,
                'price_snapshot' => round($price, 2),
                'duration_snapshot' => $duration,
                'variation_options' => $variationOptions,
                'add_ons' => $addOns,
            ];

            $subtotal += $price;
            $addonTotal += $addOnPrice;
            $totalDuration += $duration + $addOnDuration;
        }

        return [
            'items' => $builtItems,
            'subtotal' => round($subtotal, 2),
            'addon_total' => round($addonTotal, 2),
            'total_price' => round($subtotal + $addonTotal, 2),
            'total_duration_minutes' => $totalDuration,
        ];
    }

    /**
     * @param  array<int, int>  $optionIds
     * @return array{0: array, 1: float, 2: int}
     */
    private function applyVariations(Service $service, array $optionIds, float $price, int $duration): array
    {
        $breakdown = [];
        $usedTypes = [];

        foreach ($optionIds as $optionId) {
            $adjustment = ServiceVariationOption::where('service_id', $service->id)
                ->where('variation_option_id', $optionId)
                ->where('is_active', true)
                ->with('variationOption.type')
                ->first();

            if (! $adjustment || ! $adjustment->variationOption) {
                throw ValidationException::withMessages([
                    'services' => ["The selected variation is not available for {$service->name}."],
                ]);
            }

            $typeId = $adjustment->variationOption->variation_type_id;

            if (in_array($typeId, $usedTypes, true)) {
                throw ValidationException::withMessages([
                    'services' => ["Only one value can be selected per variation type for {$service->name}."],
                ]);
            }

            $usedTypes[] = $typeId;

            $price += (float) $adjustment->price_adjustment;
            $duration += $adjustment->duration_adjustment_minutes;

            $breakdown[] = [
                'variation_option_id' => $optionId,
                'variation_type_name_snapshot' => $adjustment->variationOption->type->name,
                'name_snapshot' => $adjustment->variationOption->name,
                'price_adjustment_snapshot' => $adjustment->price_adjustment,
                'duration_adjustment_snapshot' => $adjustment->duration_adjustment_minutes,
            ];
        }

        return [$breakdown, $price, $duration];
    }

    /**
     * @param  array<int, int>  $addOnIds
     * @return array{0: array, 1: float, 2: int}
     */
    private function applyAddOns(Service $service, array $addOnIds): array
    {
        $breakdown = [];
        $price = 0.0;
        $duration = 0;

        foreach ($addOnIds as $addOnId) {
            $addOn = $service->addOns()->where('add_ons.id', $addOnId)->where('add_ons.is_active', true)->first();

            if (! $addOn) {
                throw ValidationException::withMessages([
                    'services' => ["The selected add-on is not available for {$service->name}."],
                ]);
            }

            $price += (float) $addOn->price_modifier;
            $duration += $addOn->duration_modifier_minutes;

            $breakdown[] = [
                'add_on_id' => $addOn->id,
                'name_snapshot' => $addOn->name,
                'price_snapshot' => $addOn->price_modifier,
                'duration_snapshot' => $addOn->duration_modifier_minutes,
            ];
        }

        return [$breakdown, $price, $duration];
    }
}
