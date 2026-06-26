<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

readonly class WorkflowActionResult implements \JsonSerializable
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  list<string>  $warnings
     */
    public function __construct(
        public string $status,
        public ?string $nextNodeId = null,
        public array $metadata = [],
        public array $warnings = [],
        public ?string $error = null,
        public bool $halt = false,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'next_node_id' => $this->nextNodeId,
            'metadata' => $this->metadata,
            'warnings' => $this->warnings,
            'error' => $this->error,
            'halt' => $this->halt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
