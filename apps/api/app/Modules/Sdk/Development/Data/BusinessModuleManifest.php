<?php

namespace App\Modules\Sdk\Development\Data;

use App\Modules\Sdk\Development\Enums\BusinessModuleType;

readonly class BusinessModuleManifest implements \JsonSerializable
{
    /**
     * @param  list<BusinessModuleCapabilityDefinition>  $capabilities
     * @param  list<BusinessModulePermissionDefinition>  $permissions
     * @param  list<BusinessModuleRouteDefinition>  $routes
     * @param  list<array<string, mixed>>  $entities
     * @param  list<array<string, mixed>>  $forms
     * @param  list<array<string, mixed>>  $tables
     * @param  list<array<string, mixed>>  $dashboards
     * @param  list<array<string, mixed>>  $reports
     * @param  list<array<string, mixed>>  $workflows
     * @param  list<string>  $dependencies
     * @param  array<string, mixed>  $settings
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $moduleKey,
        public string $name,
        public ?string $description = null,
        public string $type = BusinessModuleType::Business->value,
        public string $version = '0.1.0',
        public array $capabilities = [],
        public array $permissions = [],
        public array $routes = [],
        public array $entities = [],
        public array $forms = [],
        public array $tables = [],
        public array $dashboards = [],
        public array $reports = [],
        public array $workflows = [],
        public array $dependencies = [],
        public array $settings = [],
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $capabilities = [];
        foreach (is_array($data['capabilities'] ?? null) ? $data['capabilities'] : [] as $capability) {
            if (is_array($capability)) {
                $capabilities[] = BusinessModuleCapabilityDefinition::fromArray($capability);
            }
        }

        $permissions = [];
        foreach (is_array($data['permissions'] ?? null) ? $data['permissions'] : [] as $permission) {
            if (is_array($permission)) {
                $permissions[] = BusinessModulePermissionDefinition::fromArray($permission);
            }
        }

        $routes = [];
        foreach (is_array($data['routes'] ?? null) ? $data['routes'] : [] as $route) {
            if (is_array($route)) {
                $routes[] = BusinessModuleRouteDefinition::fromArray($route);
            }
        }

        return new self(
            moduleKey: (string) ($data['module_key'] ?? $data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: (string) ($data['type'] ?? BusinessModuleType::Business->value),
            version: (string) ($data['version'] ?? '0.1.0'),
            capabilities: $capabilities,
            permissions: $permissions,
            routes: $routes,
            entities: is_array($data['entities'] ?? null) ? $data['entities'] : [],
            forms: is_array($data['forms'] ?? null) ? $data['forms'] : [],
            tables: is_array($data['tables'] ?? null) ? $data['tables'] : [],
            dashboards: is_array($data['dashboards'] ?? null) ? $data['dashboards'] : [],
            reports: is_array($data['reports'] ?? null) ? $data['reports'] : [],
            workflows: is_array($data['workflows'] ?? null) ? $data['workflows'] : [],
            dependencies: is_array($data['dependencies'] ?? null) ? array_values(array_map('strval', $data['dependencies'])) : [],
            settings: is_array($data['settings'] ?? null) ? $data['settings'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'module_key' => $this->moduleKey,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'version' => $this->version,
            'capabilities' => array_map(fn (BusinessModuleCapabilityDefinition $c) => $c->toArray(), $this->capabilities),
            'permissions' => array_map(fn (BusinessModulePermissionDefinition $p) => $p->toArray(), $this->permissions),
            'routes' => array_map(fn (BusinessModuleRouteDefinition $r) => $r->toArray(), $this->routes),
            'entities' => $this->entities,
            'forms' => $this->forms,
            'tables' => $this->tables,
            'dashboards' => $this->dashboards,
            'reports' => $this->reports,
            'workflows' => $this->workflows,
            'dependencies' => $this->dependencies,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
