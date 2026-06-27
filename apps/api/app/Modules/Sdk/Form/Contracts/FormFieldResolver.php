<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormField;

interface FormFieldResolver
{
    /**
     * @return list<FormField>
     */
    public function resolve(FormDefinition $definition, array $context = []): array;

    public function resolveField(FormDefinition $definition, string $fieldKey, array $context = []): ?FormField;
}
