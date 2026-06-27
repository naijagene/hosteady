<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowPackageSearchResult implements \JsonSerializable
{
    /**
     * @param  list<WorkflowPackageReference>  $packages
     */
    public function __construct(
        public array $packages,
        public int $total,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $packages = [];
        foreach (is_array($data['packages'] ?? null) ? $data['packages'] : [] as $package) {
            if (is_array($package)) {
                $packages[] = WorkflowPackageReference::fromArray($package);
            }
        }

        return new self(
            packages: $packages,
            total: (int) ($data['total'] ?? count($packages)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'packages' => array_map(fn (WorkflowPackageReference $p) => $p->toArray(), $this->packages),
            'total' => $this->total,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
