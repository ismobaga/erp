<?php

namespace App\Support;

use App\Models\Company;

class CompanyFeatureManager
{
    /** @var array<string, bool> */
    protected static array $cache = [];

    public function enabled(string $key, ?Company $company = null): bool
    {
        return $this->enabledFor($company ?? currentCompany(), $key);
    }

    public function enabledFor(?Company $company, string $key): bool
    {
        $feature = $this->normalize($key);

        if ($feature === '') {
            return true;
        }

        if ($this->isCore($feature)) {
            return true;
        }

        if (! $this->isAdvanced($feature) || $company === null) {
            return false;
        }

        $options = $this->optionsFor($company);
        $cacheKey = ($company->getKey() ?? 'none') . ':' . md5(json_encode($options) ?: $feature) . ':' . $feature;

        return self::$cache[$cacheKey] ??= (bool) ($options[$feature] ?? false);
    }

    /**
     * @return array<string, bool>
     */
    public function optionsFor(?Company $company): array
    {
        return array_replace($this->defaults(), is_array($company?->advanced_options) ? $company->advanced_options : []);
    }

    /**
     * @return array<string, bool>
     */
    public function defaults(): array
    {
        return (array) config('erp.company_features.defaults', []);
    }

    public function isCore(string $key): bool
    {
        return in_array($this->normalize($key), $this->coreFeatures(), true);
    }

    public function isAdvanced(string $key): bool
    {
        return in_array($this->normalize($key), $this->advancedFeatures(), true);
    }

    /**
     * @return array<int, string>
     */
    protected function coreFeatures(): array
    {
        return array_values(array_map(fn (string $key): string => $this->normalize($key), (array) config('erp.company_features.core', [])));
    }

    /**
     * @return array<int, string>
     */
    protected function advancedFeatures(): array
    {
        return array_values(array_map(fn (string $key): string => $this->normalize($key), (array) config('erp.company_features.advanced', [])));
    }

    protected function normalize(string $key): string
    {
        return trim(strtolower($key));
    }
}
