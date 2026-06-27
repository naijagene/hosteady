<?php

namespace App\Modules\Sdk\Form\Data;

readonly class FormStatistics implements \JsonSerializable
{
    public function __construct(
        public int $definitions,
        public int $submissions,
        public int $drafts,
        public array $registeredModules = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            definitions: (int) ($data['definitions'] ?? 0),
            submissions: (int) ($data['submissions'] ?? 0),
            drafts: (int) ($data['drafts'] ?? 0),
            registeredModules: is_array($data['registered_modules'] ?? null) ? $data['registered_modules'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'definitions' => $this->definitions,
            'submissions' => $this->submissions,
            'drafts' => $this->drafts,
            'registered_modules' => $this->registeredModules,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
