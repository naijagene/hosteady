<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormSubmissionRequest;
use App\Modules\Sdk\Form\Data\FormValidationReport;

interface FormValidator
{
    public function validate(FormDefinition $definition): FormValidationReport;

    public function validateSubmission(
        FormSubmissionRequest $request,
        FormDefinition $definition,
    ): FormValidationReport;
}
