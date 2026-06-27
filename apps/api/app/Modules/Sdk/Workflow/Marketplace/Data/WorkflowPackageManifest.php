<?php

namespace App\Modules\Sdk\Workflow\Marketplace\Data;

readonly class WorkflowPackageManifest implements \JsonSerializable
{
    /**
     * @param  list<string>  $tags
     * @param  list<WorkflowDependency>  $requires
     * @param  array<string, mixed>  $workflow
     * @param  array<string, mixed>  $canvas
     * @param  list<array<string, mixed>>  $variables
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $version,
        public ?string $moduleKey = null,
        public string $engine = 'heos',
        public ?string $engineVersion = null,
        public ?string $author = null,
        public ?string $license = null,
        public ?string $description = null,
        public array $tags = [],
        public array $requires = [],
        public array $workflow = [],
        public array $canvas = [],
        public array $variables = [],
        public array $metadata = [],
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $requires = [];

        foreach (is_array($data['requires'] ?? null) ? $data['requires'] : [] as $dependency) {
            if (is_array($dependency)) {
                $requires[] = WorkflowDependency::fromArray($dependency);
            }
        }

        return new self(
            key: (string) ($data['key'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            version: (string) ($data['version'] ?? '1.0.0'),
            moduleKey: isset($data['module_key']) ? (string) $data['module_key'] : null,
            engine: (string) ($data['engine'] ?? 'heos'),
            engineVersion: isset($data['engine_version']) ? (string) $data['engine_version'] : null,
            author: isset($data['author']) ? (string) $data['author'] : null,
            license: isset($data['license']) ? (string) $data['license'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            tags: is_array($data['tags'] ?? null) ? array_values(array_map('strval', $data['tags'])) : [],
            requires: $requires,
            workflow: is_array($data['workflow'] ?? null) ? $data['workflow'] : [],
            canvas: is_array($data['canvas'] ?? null) ? $data['canvas'] : [],
            variables: is_array($data['variables'] ?? null) ? $data['variables'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'version' => $this->version,
            'module_key' => $this->moduleKey,
            'engine' => $this->engine,
            'engine_version' => $this->engineVersion,
            'author' => $this->author,
            'license' => $this->license,
            'description' => $this->description,
            'tags' => $this->tags,
            'requires' => array_map(fn (WorkflowDependency $d) => $d->toArray(), $this->requires),
            'workflow' => $this->workflow,
            'canvas' => $this->canvas,
            'variables' => $this->variables,
            'metadata' => $this->metadata,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
