<?php

namespace App\Modules\Sdk\Rules\Contracts;

interface RuleFactProvider
{
    /** @return array<string, mixed> */
    public function factsFromArray(array $values): array;

    /** @return list<\App\Modules\Sdk\Rules\Data\RuleFact> */
    public function toFactList(array $facts): array;
}
