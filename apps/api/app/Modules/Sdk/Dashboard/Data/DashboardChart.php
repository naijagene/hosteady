<?php

namespace App\Modules\Sdk\Dashboard\Data;

readonly class DashboardChart implements \JsonSerializable
{
    /**
     * @param  list<DashboardDataset>  $datasets
     */
    public function __construct(
        public string $type = 'line',
        public array $datasets = [],
        public array $labels = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $datasets = [];
        foreach (is_array($data['datasets'] ?? null) ? $data['datasets'] : [] as $dataset) {
            if (is_array($dataset)) {
                $datasets[] = DashboardDataset::fromArray($dataset);
            }
        }

        return new self(
            type: (string) ($data['type'] ?? 'line'),
            datasets: $datasets,
            labels: is_array($data['labels'] ?? null) ? $data['labels'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'datasets' => array_map(fn (DashboardDataset $d) => $d->toArray(), $this->datasets),
            'labels' => $this->labels,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
