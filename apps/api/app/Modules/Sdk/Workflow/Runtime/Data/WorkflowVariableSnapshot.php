<?php

namespace App\Modules\Sdk\Workflow\Runtime\Data;

readonly class WorkflowVariableSnapshot implements \JsonSerializable
{
    /**
     * @param  mixed  $value
     */
    public function __construct(
        public string $key,
        public mixed $value,
        public string $source,
        public ?string $snapshotAt = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'source' => $this->source,
            'snapshot_at' => $this->snapshotAt,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
