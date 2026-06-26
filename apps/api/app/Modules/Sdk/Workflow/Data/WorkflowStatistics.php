<?php

namespace App\Modules\Sdk\Workflow\Data;

readonly class WorkflowStatistics implements \JsonSerializable
{
    public function __construct(
        public int $definitions,
        public int $published,
        public int $drafts,
        public int $archived,
        public int $categories,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'published' => $this->published,
            'drafts' => $this->drafts,
            'archived' => $this->archived,
            'categories' => $this->categories,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
