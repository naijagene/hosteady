<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormValidationReport implements \JsonSerializable
{
    /**
     * @param  list<FormValidationIssue>  $issues
     */
    public function __construct(
        public string $moduleKey,
        public string $formKey,
        public bool $valid,
        public array $issues = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $issues = [];
        foreach (is_array($data['issues'] ?? null) ? $data['issues'] : [] as $issue) {
            if (is_array($issue)) {
                $issues[] = FormValidationIssue::fromArray($issue);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? ''),
            formKey: (string) ($data['form_key'] ?? ''),
            valid: (bool) ($data['valid'] ?? false),
            issues: $issues,
        );
    }

    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'form_key' => $this->formKey,
            'valid' => $this->valid,
            'issues' => array_map(fn (FormValidationIssue $i) => $i->toArray(), $this->issues),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
