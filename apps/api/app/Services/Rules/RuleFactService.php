<?php

namespace App\Services\Rules;

use App\Modules\Sdk\Rules\Contracts\RuleFactProvider;
use App\Modules\Sdk\Rules\Data\RuleFact;

class RuleFactService implements RuleFactProvider
{
    public function factsFromArray(array $values): array
    {
        return $values;
    }

    public function toFactList(array $facts): array
    {
        $list = [];
        foreach ($facts as $key => $value) {
            $list[] = new RuleFact(
                key: (string) $key,
                value: $value,
                source: 'context',
            );
        }

        return $list;
    }
}
