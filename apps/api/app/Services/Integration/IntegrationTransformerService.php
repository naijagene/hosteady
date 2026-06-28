<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Contracts\IntegrationTransformer;

class IntegrationTransformerService implements IntegrationTransformer
{
    public function transform(array $payload, string $transformType, array $config): array
    {
        return match ($transformType) {
            'field_mapping' => app(IntegrationMapperService::class)->map(
                $payload,
                is_array($config['mapping'] ?? null) ? $config['mapping'] : [],
                $transformType,
            ),
            'static_mapping' => is_array($config['values'] ?? null) ? array_merge($payload, $config['values']) : $payload,
            'template_mapping' => $this->applyTemplate($payload, $config),
            default => $payload,
        };
    }

    private function applyTemplate(array $payload, array $config): array
    {
        $template = is_array($config['template'] ?? null) ? $config['template'] : [];

        return $template !== [] ? array_replace_recursive($template, $payload) : $payload;
    }
}
