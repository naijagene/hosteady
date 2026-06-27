<?php

namespace App\Modules\Sdk\Entity\Data;

readonly class EntityStatistics implements \JsonSerializable
{
    public function __construct(
        public int $definitions,
        public int $relationships,
        public int $comments,
        public int $tags,
        public int $activityLogs,
        public array $registeredModules = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? 0),
            relationships: (int) ($data['relationships'] ?? 0),
            comments: (int) ($data['comments'] ?? 0),
            tags: (int) ($data['tags'] ?? 0),
            activityLogs: (int) ($data['activity_logs'] ?? 0),
            registeredModules: is_array($data['registered_modules'] ?? null) ? $data['registered_modules'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'relationships' => $this->relationships,
            'comments' => $this->comments,
            'tags' => $this->tags,
            'activity_logs' => $this->activityLogs,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
