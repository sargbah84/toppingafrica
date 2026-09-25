<?php

declare(strict_types=1);

namespace App\Services\Promotions;

/**
 * Resolves a package + add-on selection against config/promotions.php.
 * Prices always come from config, never from the submitted form.
 */
class PromotionPricing
{
    public function packageExists(string $package): bool
    {
        return config("promotions.packages.{$package}") !== null;
    }

    /**
     * Add-on keys that may be bought with the given package.
     *
     * @return array<int, string>
     */
    public function availableAddons(string $package): array
    {
        return collect(config('promotions.addons', []))
            ->reject(fn (array $addon) => in_array($package, $addon['excluded_packages'] ?? [], true))
            ->keys()
            ->all();
    }

    /**
     * Drops unknown, excluded and duplicate add-ons.
     *
     * @param  array<int, string>  $addons
     * @return array<int, string>
     */
    public function sanitizeAddons(string $package, array $addons): array
    {
        return array_values(array_intersect($this->availableAddons($package), $addons));
    }

    /**
     * Stripe-ready line items.
     *
     * @param  array<int, string>  $addons
     * @return array<int, array{name: string, amount: int}>
     */
    public function lineItems(string $package, array $addons): array
    {
        $items = [[
            'name' => config("promotions.packages.{$package}.name").' package',
            'amount' => (int) config("promotions.packages.{$package}.price"),
        ]];

        foreach ($this->sanitizeAddons($package, $addons) as $key) {
            $items[] = [
                'name' => config("promotions.addons.{$key}.name"),
                'amount' => (int) config("promotions.addons.{$key}.price"),
            ];
        }

        return $items;
    }

    /** @param  array<int, string>  $addons */
    public function total(string $package, array $addons): int
    {
        return array_sum(array_column($this->lineItems($package, $addons), 'amount'));
    }
}
