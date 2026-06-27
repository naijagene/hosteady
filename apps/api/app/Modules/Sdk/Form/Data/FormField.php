<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormField implements \JsonSerializable
{
    /**
     * @param  list<FormFieldOption>  $options
     * @param  list<FormFieldValidationRule>  $validationRules
     * @param  list<FormCondition>  $conditions
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $required = false,
        public bool $readOnly = false,
        public bool $searchable = false,
        public array $options = [],
        public array $validationRules = [],
        public array $conditions = [],
        public array $metadata = [],
        public ?string $sectionKey = null,
        public ?string $tabKey = null,
        public ?string $groupKey = null,
        public bool $repeatable = false,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $options = [];
        foreach (is_array($data['options'] ?? null) ? $data['options'] : [] as $option) {
            if (is_array($option)) {
                $options[] = FormFieldOption::fromArray($option);
            }
        }

        $validationRules = [];
        foreach (is_array($data['validation_rules'] ?? null) ? $data['validation_rules'] : [] as $rule) {
            if (is_array($rule)) {
                $validationRules[] = FormFieldValidationRule::fromArray($rule);
            }
        }

        $conditions = [];
        foreach (is_array($data['conditions'] ?? null) ? $data['conditions'] : [] as $condition) {
            if (is_array($condition)) {
                $conditions[] = FormCondition::fromArray($condition);
            }
        }

        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'string'),
            required: (bool) ($data['required'] ?? false),
            readOnly: (bool) ($data['read_only'] ?? $data['readonly'] ?? false),
            searchable: (bool) ($data['searchable'] ?? false),
            options: $options,
            validationRules: $validationRules,
            conditions: $conditions,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            sectionKey: isset($data['section_key']) ? (string) $data['section_key'] : null,
            tabKey: isset($data['tab_key']) ? (string) $data['tab_key'] : null,
            groupKey: isset($data['group_key']) ? (string) $data['group_key'] : null,
            repeatable: (bool) ($data['repeatable'] ?? false),
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'required' => $this->required,
            'read_only' => $this->readOnly,
            'searchable' => $this->searchable,
            'options' => array_map(fn (FormFieldOption $o) => $o->toArray(), $this->options),
            'validation_rules' => array_map(fn (FormFieldValidationRule $r) => $r->toArray(), $this->validationRules),
            'conditions' => array_map(fn (FormCondition $c) => $c->toArray(), $this->conditions),
            'metadata' => $this->metadata,
            'section_key' => $this->sectionKey,
            'tab_key' => $this->tabKey,
            'group_key' => $this->groupKey,
            'repeatable' => $this->repeatable,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
