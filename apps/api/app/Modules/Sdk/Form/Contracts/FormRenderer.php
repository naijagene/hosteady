<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;

interface FormRenderer
{
    public function render(FormDefinition $definition, array $context = []): array;
}
