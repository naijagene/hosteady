<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

use App\Modules\Sdk\Workflow\Marketplace\Enums\WorkflowCompatibilityStatus;

readonly class WorkflowCompatibilityReport implements \JsonSerializable
{
    /**
     * @param  list<string>  $issues
     * @param  list<string>  $warnings
     * @param  list<WorkflowDependency>  $dependencies
     */
    public function __construct(
        public string $packagePublicId,
        public string $status,
        public array $issues = [],
        public array $warnings = [],
        public array $dependencies = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $dependencies = [];
        foreach (is_array($data['dependencies'] ?? null) ? $data['dependencies'] : [] as $dependency) {
            if (is_array($dependency)) {
                $dependencies[] = WorkflowDependency::fromArray($dependency);
            }
        }

        return new self(
            packagePublicId: (string) ($data['package_public_id'] ?? ''),
            status: (string) ($data['status'] ?? WorkflowCompatibilityStatus::Compatible->value),
            issues: is_array($data['issues'] ?? null) ? array_values(array_map('strval', $data['issues'])) : [],
            warnings: is_array($data['warnings'] ?? null) ? array_values(array_map('strval', $data['warnings'])) : [],
            dependencies: $dependencies,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'package_public_id' => $this->packagePublicId,
            'status' => $this->status,
            'issues' => $this->issues,
            'warnings' => $this->warnings,
            'dependencies' => array_map(fn (WorkflowDependency $d) => $d->toArray(), $this->dependencies),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
