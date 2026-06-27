<?php

namespace App\Modules\Sdk\Table\Data;

readonly class TableColumn implements \JsonSerializable
{
    /**
     * @param  list<TableColumnOption>  $options
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type,
        public bool $sortable = true,
        public bool $filterable = true,
        public bool $searchable = false,
        public bool $visible = true,
        public ?int $width = null,
        public array $options = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $options = [];
        foreach (is_array($data['options'] ?? null) ? $data['options'] : [] as $option) {
            if (is_array($option)) {
                $options[] = TableColumnOption::fromArray($option);
            }
        }

        return new self(
            key: (string) ($data['key'] ?? ''),
            label: (string) ($data['label'] ?? $data['name'] ?? ''),
            type: (string) ($data['type'] ?? 'text'),
            sortable: (bool) ($data['sortable'] ?? true),
            filterable: (bool) ($data['filterable'] ?? true),
            searchable: (bool) ($data['searchable'] ?? false),
            visible: (bool) ($data['visible'] ?? true),
            width: isset($data['width']) ? (int) $data['width'] : null,
            options: $options,
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'searchable' => $this->searchable,
            'visible' => $this->visible,
            'width' => $this->width,
            'options' => array_map(fn (TableColumnOption $o) => $o->toArray(), $this->options),
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
