<?php

namespace App\Modules\Sdk\Form\Contracts;

use App\Modules\Sdk\Form\Data\FormDefinition;

interface FormRegistry
{
    public function register(mixed $source): FormDefinition;

    public function update(FormDefinition $definition): FormDefinition;

    public function find(string $moduleKey, string $formKey): ?FormDefinition;

    /**
     * @return list<FormDefinition>
     */
    public function list(?string $moduleKey = null): array;

    /**
     * @param  list<array<string, mixed>>  $forms
     * @return list<FormDefinition>
     */
    public function registerFromManifestForms(array $forms, string $moduleKey): array;
}
