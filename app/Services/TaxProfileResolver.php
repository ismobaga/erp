<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Arr;

class TaxProfileResolver
{
    public function resolveForClient(?Client $client): array
    {
        $default = array_merge([
            'code' => 'STANDARD',
            'label' => 'Standard tax profile',
            'rate' => 0.0,
            'mode' => 'exclusive',
            'matched' => false,
        ], (array) config('erp.tax_profiles.default', []));

        $countries = (array) config('erp.tax_profiles.countries', []);
        $country = $this->normalizeKey($client?->country ?: config('erp.tax_profiles.default_country', ''));
        $region = $this->normalizeKey($client?->city);

        $countryConfig = $this->resolveNamedConfig($countries, $country);
        $countryProfile = Arr::except($countryConfig, ['regions']);
        $regionProfile = $region !== null
            ? $this->resolveNamedConfig((array) ($countryConfig['regions'] ?? []), $region)
            : [];

        $profile = array_merge($default, $countryProfile, $regionProfile);
        $profile['rate'] = (float) ($profile['rate'] ?? 0.0);
        $profile['mode'] = (string) ($profile['mode'] ?? 'exclusive');
        $profile['matched'] = !empty($countryProfile) || !empty($regionProfile) || $profile['rate'] > 0;
        $profile['country'] = $client?->country ?: null;
        $profile['region'] = $client?->city ?: null;

        return $profile;
    }

    public function calculateForClient(float $subtotal, ?Client $client): array
    {
        $profile = $this->resolveForClient($client);
        $rate = max(0.0, (float) ($profile['rate'] ?? 0.0));
        $mode = (string) ($profile['mode'] ?? 'exclusive');

        if ($rate <= 0) {
            return [
                'profile' => $profile,
                'tax_total' => 0.0,
                'total' => $subtotal,
                'matched' => (bool) ($profile['matched'] ?? false),
            ];
        }

        $taxTotal = match ($mode) {
            'inclusive' => round($subtotal - ($subtotal / (1 + ($rate / 100))), 2),
            'exempt', 'none' => 0.0,
            default => round($subtotal * ($rate / 100), 2),
        };

        $total = $mode === 'inclusive' ? $subtotal : round($subtotal + $taxTotal, 2);

        return [
            'profile' => $profile,
            'tax_total' => $taxTotal,
            'total' => $total,
            'matched' => true,
        ];
    }

    protected function resolveNamedConfig(array $configs, ?string $target): array
    {
        if ($target === null) {
            return [];
        }

        foreach ($configs as $name => $config) {
            if ($this->normalizeKey((string) $name) === $target) {
                return (array) $config;
            }
        }

        return [];
    }

    protected function normalizeKey(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return mb_strtolower($value);
    }
}
