<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormLayout;

interface FormLayoutProvider
{
    public function layout(FormDefinition $definition): FormLayout;
}
