<?php

namespace App\Services\Integration;

use App\Modules\Sdk\Integration\Contracts\IntegrationMapper as IntegrationMapperContract;

class IntegrationMapperService implements IntegrationMapperContract
{
    public function map(array $source, array $mapping, string $transformType): array
    {
        if ($transformType === 'pass_through' || $mapping === []) {
            return $source;
        }

        $result = [];

        foreach ($mapping as $target => $sourceKey) {
            if (is_string($sourceKey) && array_key_exists($sourceKey, $source)) {
                $result[$target] = $source[$sourceKey];
            } elseif (is_array($sourceKey)) {
                $result[$target] = $sourceKey;
            }
        }

        return $result !== [] ? $result : $source;
    }
}
