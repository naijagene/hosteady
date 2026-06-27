<?php

namespace App\Modules\Sdk\Form\Data;

use App\Modules\Sdk\Form\Enums\FormStatus;
use App\Modules\Sdk\Form\Enums\FormType;
use App\Modules\Sdk\Form\Enums\FormVisibility;

readonly class FormDefinition implements \JsonSerializable
{
    /**
     * @param  list<FormSection>  $sections
     * @param  list<FormField>  $fields
     * @param  list<FormAction>  $actions
     * @param  list<FormCondition>  $conditions
     * @param  list<FormFieldValidationRule>  $validationRules
     */
    public function __construct(
        public string $moduleKey,
        public string $formKey,
        public string $name,
        public ?string $publicId = null,
        public ?string $organizationId = null,
        public ?string $workspaceId = null,
        public ?string $entityKey = null,
        public ?string $description = null,
        public string $type = FormType::Create->value,
        public string $status = FormStatus::Registered->value,
        public string $visibility = FormVisibility::Organization->value,
        public ?FormLayout $layout = null,
        public array $sections = [],
        public array $fields = [],
        public array $actions = [],
        public array $conditions = [],
        public array $validationRules = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $sections = [];
        foreach (is_array($data['sections'] ?? null) ? $data['sections'] : [] as $section) {
            if (is_array($section)) {
                $sections[] = FormSection::fromArray($section);
            }
        }

        $fields = [];
        foreach (is_array($data['fields'] ?? null) ? $data['fields'] : [] as $field) {
            if (is_array($field)) {
                $fields[] = FormField::fromArray($field);
            }
        }

        $actions = [];
        foreach (is_array($data['actions'] ?? null) ? $data['actions'] : [] as $action) {
            if (is_array($action)) {
                $actions[] = FormAction::fromArray($action);
            }
        }

        $conditions = [];
        foreach (is_array($data['conditions'] ?? null) ? $data['conditions'] : [] as $condition) {
            if (is_array($condition)) {
                $conditions[] = FormCondition::fromArray($condition);
            }
        }

        $validationRules = [];
        foreach (is_array($data['validation_rules'] ?? null) ? $data['validation_rules'] : [] as $rule) {
            if (is_array($rule)) {
                $validationRules[] = FormFieldValidationRule::fromArray($rule);
            }
        }

        $layout = null;
        if (is_array($data['layout'] ?? null)) {
            $layout = FormLayout::fromArray($data['layout']);
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            formKey: (string) ($data['form_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? $data['label'] ?? ''),
            publicId: isset($data['public_id']) ? (string) $data['public_id'] : null,
            organizationId: isset($data['organization_id']) ? (string) $data['organization_id'] : null,
            workspaceId: isset($data['workspace_id']) ? (string) $data['workspace_id'] : null,
            entityKey: isset($data['entity_key']) ? (string) $data['entity_key'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: (string) ($data['type'] ?? FormType::Create->value),
            status: (string) ($data['status'] ?? FormStatus::Registered->value),
            visibility: (string) ($data['visibility'] ?? FormVisibility::Organization->value),
            layout: $layout,
            sections: $sections,
            fields: $fields,
            actions: $actions,
            conditions: $conditions,
            validationRules: $validationRules,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'public_id' => $this->publicId,
            'organization_id' => $this->organizationId,
            'workspace_id' => $this->workspaceId,
            'module_key' => $this->moduleKey,
            'entity_key' => $this->entityKey,
            'form_key' => $this->formKey,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'visibility' => $this->visibility,
            'layout' => $this->layout?->toArray(),
            'sections' => array_map(fn (FormSection $s) => $s->toArray(), $this->sections),
            'fields' => array_map(fn (FormField $f) => $f->toArray(), $this->fields),
            'actions' => array_map(fn (FormAction $a) => $a->toArray(), $this->actions),
            'conditions' => array_map(fn (FormCondition $c) => $c->toArray(), $this->conditions),
            'validation_rules' => array_map(fn (FormFieldValidationRule $r) => $r->toArray(), $this->validationRules),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
