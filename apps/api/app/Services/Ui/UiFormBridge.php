<?php

namespace App\Services\Ui;

use App\Services\Form\DynamicFormRegistryService;

class UiFormBridge
{
    public function __construct(
        private readonly DynamicFormRegistryService $formRegistry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>|null
     */
    public function resolveReferenceBestEffort(?string $moduleKey, ?string $resourceKey, array $config = []): ?array
    {
        try {
            $moduleKey = $moduleKey ?? (string) ($config['module_key'] ?? '');
            $formKey = $resourceKey ?? (string) ($config['form_key'] ?? $config['resource_key'] ?? '');

            if ($moduleKey === '' || $formKey === '') {
                return null;
            }

            $definition = $this->formRegistry->find($moduleKey, $formKey);

            return $definition?->toArray();
        } catch (\Throwable) {
            return null;
        }
    }
}
