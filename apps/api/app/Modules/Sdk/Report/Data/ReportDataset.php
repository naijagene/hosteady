<?php

namespace App\Modules\Sdk\Report\Data;

readonly class ReportDataset implements \JsonSerializable
{
    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<ReportMetric>  $metrics
     * @param  list<ReportChart>  $charts
     */
    public function __construct(
        public array $rows = [],
        public array $metrics = [],
        public array $charts = [],
        public array $aggregates = [],
        public array $metadata = [],
    ) {
    }

    public static function fromArray(array $data): self
    {
        $metrics = [];
        foreach (is_array($data['metrics'] ?? null) ? $data['metrics'] : [] as $metric) {
            if (is_array($metric)) {
                $metrics[] = ReportMetric::fromArray($metric);
            }
        }

        $charts = [];
        foreach (is_array($data['charts'] ?? null) ? $data['charts'] : [] as $chart) {
            if (is_array($chart)) {
                $charts[] = ReportChart::fromArray($chart);
            }
        }

        return new self(
            rows: is_array($data['rows'] ?? null) ? $data['rows'] : [],
            metrics: $metrics,
            charts: $charts,
            aggregates: is_array($data['aggregates'] ?? null) ? $data['aggregates'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    public function toArray(): array
    {
        return [
            'rows' => $this->rows,
            'metrics' => array_map(fn (ReportMetric $m) => $m->toArray(), $this->metrics),
            'charts' => array_map(fn (ReportChart $c) => $c->toArray(), $this->charts),
            'aggregates' => $this->aggregates,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
