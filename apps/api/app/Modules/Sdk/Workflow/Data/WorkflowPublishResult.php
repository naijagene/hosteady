<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowPublishResult implements \JsonSerializable
{
    public function __construct(
        public WorkflowDefinitionReference $definition,
        public WorkflowVersionData $publishedVersion,
        public WorkflowValidationReport $validationReport,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'definition' => $this->definition->toArray(),
            'published_version' => $this->publishedVersion->toArray(),
            'validation_report' => $this->validationReport->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
