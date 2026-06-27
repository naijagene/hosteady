<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;

interface FormDefinitionProvider
{
    public function moduleKey(): string;

    public function formKey(): string;

    public function definition(): FormDefinition;
}
