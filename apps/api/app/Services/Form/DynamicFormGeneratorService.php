<?php

namespace App\Services\Form;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;
use App\Modules\Sdk\Form\Data\FormAction;
use App\Modules\Sdk\Form\Data\FormDefinition;
use App\Modules\Sdk\Form\Data\FormField;
use App\Modules\Sdk\Form\Data\FormFieldOption;
use App\Modules\Sdk\Form\Data\FormFieldValidationRule;
use App\Modules\Sdk\Form\Data\FormLayout;
use App\Modules\Sdk\Form\Data\FormSection;
use App\Modules\Sdk\Form\Enums\FormLayoutType;
use App\Modules\Sdk\Form\Enums\FormStatus;
use App\Modules\Sdk\Form\Enums\FormType;
use App\Modules\Sdk\Form\Enums\FormVisibility;
use App\Services\Entity\EnterpriseEntityRegistryService;

class DynamicFormGeneratorService
{
    public function __construct(
        private readonly EnterpriseEntityRegistryService $entityRegistryService,
    ) {
    }

    public function generateCreateForm(string $moduleKey, string $entityKey): FormDefinition
    {
        return $this->generate($moduleKey, $entityKey, FormType::Create->value);
    }

    public function generateEditForm(string $moduleKey, string $entityKey): FormDefinition
    {
        return $this->generate($moduleKey, $entityKey, FormType::Edit->value);
    }

    public function generateViewForm(string $moduleKey, string $entityKey): FormDefinition
    {
        return $this->generate($moduleKey, $entityKey, FormType::View->value);
    }

    public function generate(string $moduleKey, string $entityKey, string $formType): FormDefinition
    {
        $entity = $this->entityRegistryService->find($moduleKey, $entityKey);

        if ($entity === null) {
            throw new \InvalidArgumentException(sprintf(
                'Entity definition [%s.%s] was not found.',
                $moduleKey,
                $entityKey,
            ));
        }

        return $this->generateFromEntity($entity, $formType);
    }

    public function generateFromEntity(EntityDefinition $entity, string $formType): FormDefinition
    {
        $formKey = sprintf('%s_%s', $entity->entityKey, $formType);
        $readOnly = $formType === FormType::View->value;

        $fields = [];
        $validationRules = [];

        foreach ($entity->fields as $field) {
            $formField = $this->mapEntityField($field, $readOnly);
            $fields[] = $formField;

            if ($field->required && ! $readOnly) {
                $validationRules[] = new FormFieldValidationRule(
                    field: $field->key,
                    rule: 'required',
                    message: sprintf('%s is required.', $field->label),
                );
            }
        }

        $actions = $this->defaultActions($formType);

        return new FormDefinition(
            moduleKey: $entity->moduleKey,
            formKey: $formKey,
            name: sprintf('%s %s form', $entity->name, $formType),
            entityKey: $entity->entityKey,
            description: $entity->description,
            type: $formType,
            status: FormStatus::Registered->value,
            visibility: FormVisibility::Organization->value,
            layout: new FormLayout(type: FormLayoutType::Sections->value, columns: 2),
            sections: [
                new FormSection(
                    key: 'main',
                    label: $entity->name,
                    description: $entity->description,
                    order: 0,
                ),
            ],
            fields: $fields,
            actions: $actions,
            conditions: [],
            validationRules: $validationRules,
            metadata: [
                'generated_from_entity' => true,
                'entity_public_id' => $entity->publicId,
            ],
        );
    }

    private function mapEntityField(EntityFieldDefinition $field, bool $readOnly): FormField
    {
        $type = $this->mapFieldType($field);
        $options = $this->resolveOptions($field);

        return new FormField(
            key: $field->key,
            label: $field->label,
            type: $type,
            required: $field->required && ! $readOnly,
            readOnly: $readOnly || $type === 'display',
            searchable: $field->searchable,
            options: $options,
            validationRules: [],
            conditions: [],
            metadata: array_merge($field->metadata, [
                'entity_field_type' => $field->type,
                'description' => $field->description,
            ]),
            sectionKey: 'main',
        );
    }

    private function mapFieldType(EntityFieldDefinition $field): string
    {
        $entityType = strtolower($field->type);

        if ($entityType === 'computed') {
            return 'display';
        }

        return match ($entityType) {
            'string', 'text' => 'text',
            'integer', 'decimal', 'number' => 'number',
            'boolean' => 'checkbox',
            'date' => 'date',
            'datetime' => 'datetime',
            'enum' => 'select',
            'reference' => 'entity_selector',
            'json' => 'textarea',
            'uuid' => 'text',
            default => 'text',
        };
    }

    /**
     * @return list<FormFieldOption>
     */
    private function resolveOptions(EntityFieldDefinition $field): array
    {
        if (strtolower($field->type) !== 'enum') {
            return [];
        }

        $options = [];
        $rawOptions = is_array($field->metadata['options'] ?? null) ? $field->metadata['options'] : [];

        foreach ($rawOptions as $option) {
            if (is_array($option)) {
                $options[] = FormFieldOption::fromArray($option);

                continue;
            }

            if (is_string($option) || is_numeric($option)) {
                $value = (string) $option;
                $options[] = new FormFieldOption(value: $value, label: $value);
            }
        }

        return $options;
    }

    /**
     * @return list<FormAction>
     */
    private function defaultActions(string $formType): array
    {
        return match ($formType) {
            FormType::Create->value => [
                new FormAction(key: 'submit', label: 'Create', type: 'submit'),
                new FormAction(key: 'cancel', label: 'Cancel', type: 'cancel'),
            ],
            FormType::Edit->value => [
                new FormAction(key: 'submit', label: 'Save', type: 'submit'),
                new FormAction(key: 'cancel', label: 'Cancel', type: 'cancel'),
            ],
            FormType::View->value => [
                new FormAction(key: 'close', label: 'Close', type: 'cancel'),
            ],
            default => [
                new FormAction(key: 'submit', label: 'Submit', type: 'submit'),
            ],
        };
    }
}
