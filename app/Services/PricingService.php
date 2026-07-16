<?php

namespace App\Services;

use App\Models\Resource;

class PricingService
{
    /**
     * All slot definitions for a resource, keyed by slot_type string.
     * Returns an empty array for resources with no pricing config.
     *
     * @return array<string, array{type: string, rate: int, label: string}>
     */
    public function slots(Resource $resource): array
    {
        return config("booking.pricing.{$resource->slug}", []);
    }

    /**
     * Price for a single date at the given slot type.
     * For hourly slots, pass the number of hours booked.
     */
    public function unitPrice(Resource $resource, string $slotType, int $hours = 0): float
    {
        $slot = $this->slotConfig($resource, $slotType);

        if (! $slot) {
            return (float) $resource->price_per_day;
        }

        return $slot['type'] === 'hourly'
            ? (float) ($slot['rate'] * max(1, $hours))
            : (float) $slot['rate'];
    }

    /**
     * Total booking amount for the given dates, slot, and any add-ons.
     *
     * @param array<int, string> $dates
     */
    public function totalAmount(
        Resource $resource,
        string $slotType,
        array $dates,
        int $hours = 0,
        int $chairCount = 0,
    ): float {
        $perDate = $this->unitPrice($resource, $slotType, $hours);
        $total = $perDate * count($dates);

        // Azwar Hall chair add-on (one-time fee regardless of days)
        if ($chairCount > 0) {
            $total += $chairCount * (int) config('booking.azwar_hall_chair_rate', 10);
        }

        return $total;
    }

    private function slotConfig(Resource $resource, string $slotType): ?array
    {
        $config = config("booking.pricing.{$resource->slug}.{$slotType}");

        if (! $config) {
            return null;
        }

        // DB pricing_overrides stores a rate map: slot_type → rate (int).
        // Admin-set rates take precedence over the config defaults.
        $overrides = $resource->pricing_overrides ?? [];
        if (isset($overrides[$slotType])) {
            $config['rate'] = (int) $overrides[$slotType];
        }

        return $config;
    }
}
