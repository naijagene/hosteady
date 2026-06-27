<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormSubmissionResult;

interface FormSubmissionHandler
{
    public function submit(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): FormSubmissionResult;
}
