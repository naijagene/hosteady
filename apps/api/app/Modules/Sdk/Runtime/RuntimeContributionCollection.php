<?php

namespace App\Modules\Sdk\Runtime;

class RuntimeContributionCollection
{
    /**
     * @param  list<RuntimeContribution>  $contributions
     */
    public function __construct(
        private array $contributions = [],
    ) {
    }

    public function add(RuntimeContribution $contribution): self
    {
        return new self([...$this->contributions, $contribution]);
    }

    /**
     * @return list<RuntimeContribution>
     */
    public function all(): array
    {
        return $this->contributions;
    }

    public function count(): int
    {
        return count($this->contributions);
    }

    public function find(string $moduleKey): ?RuntimeContribution
    {
        foreach ($this->contributions as $contribution) {
            if ($contribution->moduleKey === $moduleKey) {
                return $contribution;
            }
        }

        return null;
    }

    public function ordered(): self
    {
        $contributions = $this->contributions;

        usort(
            $contributions,
            fn (RuntimeContribution $left, RuntimeContribution $right) => $left->moduleKey <=> $right->moduleKey,
        );

        return new self($contributions);
    }

    /**
     * @return array{
     *     capabilities: list<string>,
     *     navigation: list<array<string, mixed>>,
     *     feature_flags: array<string, mixed>,
     *     runtime_metadata: array<string, mixed>,
     *     diagnostics: list<array<string, mixed>>,
     *     settings_metadata: array<string, mixed>
     * }
     */
    public function merge(): array
    {
        $capabilities = [];
        $navigation = [];
        $featureFlags = [];
        $runtimeMetadata = [];
        $diagnostics = [];
        $settingsMetadata = [];

        foreach ($this->contributions as $contribution) {
            $capabilities = array_values(array_unique([...$capabilities, ...$contribution->capabilities]));
            $navigation = [...$navigation, ...$contribution->navigation];
            $featureFlags = [...$featureFlags, ...$contribution->featureFlags];
            $runtimeMetadata = $this->deepMerge($runtimeMetadata, $contribution->runtimeMetadata);
            $diagnostics = [...$diagnostics, ...$contribution->diagnostics];
            $settingsMetadata = [...$settingsMetadata, ...$contribution->settingsMetadata];
        }

        sort($capabilities);

        return [
            'capabilities' => $capabilities,
            'navigation' => $navigation,
            'feature_flags' => $featureFlags,
            'runtime_metadata' => $runtimeMetadata,
            'diagnostics' => $diagnostics,
            'settings_metadata' => $settingsMetadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function fingerprint(): array
    {
        $merged = $this->merge();

        return [
            'capabilities' => $merged['capabilities'],
            'navigation' => $this->normalizeNavigation($merged['navigation']),
            'feature_flags' => $this->normalizeMap($merged['feature_flags']),
            'runtime_metadata' => $this->normalizeMap($merged['runtime_metadata']),
            'diagnostics' => $merged['diagnostics'],
            'settings_metadata' => $this->normalizeMap($merged['settings_metadata']),
        ];
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     * @return array<string, mixed>
     */
    private function deepMerge(array $left, array $right): array
    {
        foreach ($right as $key => $value) {
            if (is_array($value) && isset($left[$key]) && is_array($left[$key]) && ! array_is_list($value)) {
                $left[$key] = $this->deepMerge($left[$key], $value);
            } else {
                $left[$key] = $value;
            }
        }

        return $left;
    }

    /**
     * @param  list<array<string, mixed>>  $navigation
     * @return list<array<string, mixed>>
     */
    private function normalizeNavigation(array $navigation): array
    {
        usort($navigation, fn (array $left, array $right) => strcmp(
            (string) ($left['route_name'] ?? $left['label'] ?? ''),
            (string) ($right['route_name'] ?? $right['label'] ?? ''),
        ));

        return $navigation;
    }

    /**
     * @param  array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function normalizeMap(array $map): array
    {
        ksort($map);

        return $map;
    }
}
