<?php

namespace App\Modules\Sdk\Application\Data;

readonly class ApplicationStatistics implements \JsonSerializable
{
    public function __construct(
        public int $registeredApps,
        public int $enabledApps,
        public int $navigationNodes,
        public int $workspaceCount
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            registeredApps: (int) ($data['registered_apps'] ?? $data['registeredApps'] ?? 0),
            enabledApps: (int) ($data['enabled_apps'] ?? $data['enabledApps'] ?? 0),
            navigationNodes: (int) ($data['navigation_nodes'] ?? $data['navigationNodes'] ?? 0),
            workspaceCount: (int) ($data['workspace_count'] ?? $data['workspaceCount'] ?? 0),
        );
    }

    public function toArray(): array
    {
        return [
            'registered_apps' => $this->registeredApps,
            'enabled_apps' => $this->enabledApps,
            'navigation_nodes' => $this->navigationNodes,
            'workspace_count' => $this->workspaceCount,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
