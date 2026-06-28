<?php

/**
 * M6-005 Enterprise Integration & Event Bus Framework generator.
 * Run: php scripts/generate_m6_integration_event_bus.php
 */

declare(strict_types=1);

$base = dirname(__DIR__);

function writeFile(string $path, string $content): void
{
    $dir = dirname($path);
    if (! is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, $content);
    echo "Wrote: {$path}\n";
}

function dtoClass(string $namespace, string $className, array $fields): string
{
    $props = [];
    $fromLines = [];
    $toLines = [];

    foreach ($fields as $name => $type) {
        $snake = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name));
        $camel = lcfirst($name);
        $default = match ($type) {
            'string' => "''",
            'int' => '0',
            'bool' => 'false',
            'array' => '[]',
            '?string' => 'null',
            default => 'null',
        };

        if (str_starts_with($type, '?')) {
            $props[] = "        public {$type} \${$camel},";
            $fromLines[] = "            {$camel}: isset(\$data['{$snake}']) ? (string) \$data['{$snake}'] : (isset(\$data['{$name}']) ? (string) \$data['{$name}'] : null),";
            $toLines[] = "            '{$snake}' => \$this->{$camel},";
        } elseif ($type === 'array') {
            $props[] = "        public array \${$camel},";
            $fromLines[] = "            {$camel}: is_array(\$data['{$snake}'] ?? \$data['{$name}'] ?? null) ? (\$data['{$snake}'] ?? \$data['{$name}']) : [],";
            $toLines[] = "            '{$snake}' => \$this->{$camel},";
        } elseif ($type === 'int') {
            $props[] = "        public int \${$camel},";
            $fromLines[] = "            {$camel}: (int) (\$data['{$snake}'] ?? \$data['{$name}'] ?? 0),";
            $toLines[] = "            '{$snake}' => \$this->{$camel},";
        } elseif ($type === 'bool') {
            $props[] = "        public bool \${$camel},";
            $fromLines[] = "            {$camel}: (bool) (\$data['{$snake}'] ?? \$data['{$name}'] ?? false),";
            $toLines[] = "            '{$snake}' => \$this->{$camel},";
        } else {
            $props[] = "        public string \${$camel},";
            $fromLines[] = "            {$camel}: (string) (\$data['{$snake}'] ?? \$data['{$name}'] ?? ''),";
            $toLines[] = "            '{$snake}' => \$this->{$camel},";
        }
    }

    $propsStr = implode("\n", $props);
    $fromStr = implode("\n", $fromLines);
    $toStr = implode("\n", $toLines);

    return <<<PHP
<?php

namespace {$namespace};

readonly class {$className} implements \\JsonSerializable
{
    public function __construct(
{$propsStr}
    ) {
    }

    public static function fromArray(array \$data): self
    {
        return new self(
{$fromStr}
        );
    }

    public function toArray(): array
    {
        return [
{$toStr}
        ];
    }

    public function jsonSerialize(): array
    {
        return \$this->toArray();
    }
}

PHP;
}

function exceptionClass(string $namespace, string $className, ?string $parent = 'IntegrationException'): string
{
    $extends = $parent ? "extends {$parent}" : 'extends \\Exception';

    return <<<PHP
<?php

namespace {$namespace};

class {$className} {$extends}
{
}

PHP;
}

function contractInterface(string $namespace, string $className, array $methods): string
{
    $methodStr = implode("\n\n", $methods);

    return <<<PHP
<?php

namespace {$namespace};

interface {$className}
{
{$methodStr}
}

PHP;
}

$count = 0;

// Exceptions
$exceptions = [
    'IntegrationException',
    'IntegrationEventException',
    'IntegrationConnectorException',
    'IntegrationDispatchException',
    'IntegrationWebhookException',
    'IntegrationCredentialException',
    'IntegrationReplayException',
];
foreach ($exceptions as $i => $ex) {
    $parent = $i === 0 ? null : 'IntegrationException';
    writeFile("{$base}/app/Modules/Sdk/Integration/Exceptions/{$ex}.php", exceptionClass('App\\Modules\\Sdk\\Integration\\Exceptions', $ex, $parent));
    $count++;
}

// Contracts
$contracts = [
    'IntegrationEventBus' => [
        '    public function publish(\\App\\Support\\Tenant\\TenantContext $context, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEventEnvelope $envelope): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEvent;',
        '    /** @return list<\\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEvent> */',
        '    public function listEvents(\\App\\Support\\Tenant\\TenantContext $context, int $limit = 50): array;',
        '    public function replay(\\App\\Support\\Tenant\\TenantContext $context, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationReplayRequest $request): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationReplayResult;',
    ],
    'IntegrationEventPublisher' => [
        '    public function publish(\\App\\Support\\Tenant\\TenantContext $context, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEventEnvelope $envelope): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEvent;',
    ],
    'IntegrationEventSubscriber' => [
        '    /** @return list<\\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEventSubscription> */',
        '    public function listSubscriptions(string $organizationId, ?string $workspaceId, int $limit = 50): array;',
        '    public function subscribe(string $organizationId, ?string $workspaceId, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEventSubscription $subscription): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEventSubscription;',
    ],
    'IntegrationConnector' => [
        '    /** @return list<\\App\\Modules\\Sdk\\Integration\\Data\\IntegrationConnectorDefinition> */',
        '    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;',
        '    public function create(string $organizationId, ?string $workspaceId, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationConnectorDefinition $definition): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationConnectorDefinition;',
        '    public function find(string $organizationId, ?string $workspaceId, string $publicId): ?\\App\\Modules\\Sdk\\Integration\\Data\\IntegrationConnectorDefinition;',
    ],
    'IntegrationEndpoint' => [
        '    /** @return list<\\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEndpointDefinition> */',
        '    public function list(string $organizationId, ?string $workspaceId, int $limit = 50): array;',
        '    public function create(string $organizationId, ?string $workspaceId, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEndpointDefinition $definition): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationEndpointDefinition;',
    ],
    'IntegrationMapper' => [
        '    public function map(array $source, array $mapping, string $transformType): array;',
    ],
    'IntegrationTransformer' => [
        '    public function transform(array $payload, string $transformType, array $config): array;',
    ],
    'IntegrationCredentialProvider' => [
        '    public function store(string $organizationId, ?string $workspaceId, \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationCredentialReference $reference, array $payload): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationCredentialReference;',
        '    public function rotate(string $organizationId, ?string $workspaceId, string $credentialKey, array $payload): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationCredentialReference;',
    ],
    'IntegrationRetryPolicy' => [
        '    public function scheduleRetry(\\App\\Models\\IntegrationDispatch $dispatch): \\App\\Models\\IntegrationDispatch;',
    ],
    'IntegrationDeadLetterQueue' => [
        '    public function enqueue(string $organizationId, ?string $workspaceId, array $payload): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationDeadLetterRecord;',
        '    public function resolve(string $organizationId, ?string $workspaceId, string $publicId): \\App\\Modules\\Sdk\\Integration\\Data\\IntegrationDeadLetterRecord;',
    ],
    'IntegrationWebhookVerifier' => [
        '    public function verify(string $authType, array $headers, string $payload, array $config): bool;',
    ],
];
foreach ($contracts as $name => $methods) {
    writeFile("{$base}/app/Modules/Sdk/Integration/Contracts/{$name}.php", contractInterface('App\\Modules\\Sdk\\Integration\\Contracts', $name, $methods));
    $count++;
}

// DTOs
$dtos = [
    'IntegrationEvent' => [
        'PublicId' => 'string', 'EventName' => 'string', 'EventVersion' => '?string',
        'Direction' => 'string', 'SourceType' => 'string', 'SourceModuleKey' => '?string',
        'SourceEntityKey' => '?string', 'SourcePublicId' => '?string',
        'CorrelationId' => '?string', 'IdempotencyKey' => '?string', 'Status' => 'string',
        'Payload' => 'array', 'Headers' => 'array', 'Metadata' => 'array',
        'OccurredAt' => '?string', 'PublishedAt' => '?string', 'CreatedAt' => '?string',
    ],
    'IntegrationEventEnvelope' => [
        'EventName' => 'string', 'EventVersion' => '?string', 'Direction' => 'string',
        'SourceType' => 'string', 'SourceModuleKey' => '?string', 'SourceEntityKey' => '?string',
        'SourcePublicId' => '?string', 'CorrelationId' => '?string', 'IdempotencyKey' => '?string',
        'Payload' => 'array', 'Headers' => 'array', 'Metadata' => 'array',
        'ForceRepublish' => 'bool',
    ],
    'IntegrationEventSubscription' => [
        'PublicId' => 'string', 'SubscriptionKey' => 'string', 'EventPattern' => 'string',
        'EndpointKey' => '?string', 'Status' => 'string', 'ModuleKey' => '?string',
        'Filters' => 'array', 'Transform' => 'array', 'RetryPolicy' => 'array', 'Metadata' => 'array',
    ],
    'IntegrationConnectorDefinition' => [
        'PublicId' => 'string', 'ConnectorKey' => 'string', 'Name' => 'string',
        'Description' => '?string', 'ConnectorType' => 'string', 'AuthType' => 'string',
        'Status' => 'string', 'ModuleKey' => '?string', 'Config' => 'array', 'Metadata' => 'array',
    ],
    'IntegrationEndpointDefinition' => [
        'PublicId' => 'string', 'ConnectorPublicId' => '?string', 'EndpointKey' => 'string',
        'Name' => 'string', 'EndpointType' => 'string', 'Direction' => 'string', 'Status' => 'string',
        'UrlTemplate' => '?string', 'Method' => '?string', 'Headers' => 'array',
        'BodyTemplate' => 'array', 'AuthReference' => 'array', 'Metadata' => 'array',
    ],
    'IntegrationCredentialReference' => [
        'PublicId' => 'string', 'ConnectorKey' => 'string', 'CredentialKey' => 'string',
        'AuthType' => 'string', 'Metadata' => 'array', 'RotatedAt' => '?string',
    ],
    'IntegrationMappingDefinition' => [
        'PublicId' => 'string', 'MappingKey' => 'string', 'ModuleKey' => '?string',
        'SourceSchema' => 'array', 'TargetSchema' => 'array', 'Mapping' => 'array',
        'TransformType' => 'string', 'Metadata' => 'array',
    ],
    'IntegrationTransformDefinition' => [
        'TransformType' => 'string', 'Config' => 'array',
    ],
    'IntegrationDispatchRequest' => [
        'EventPublicId' => 'string', 'EndpointPublicId' => '?string',
        'SubscriptionKey' => '?string', 'Metadata' => 'array',
    ],
    'IntegrationDispatchResult' => [
        'PublicId' => 'string', 'Status' => 'string', 'Attempt' => 'int',
        'MaxAttempts' => 'int', 'Request' => 'array', 'Response' => 'array',
        'ErrorMessage' => '?string', 'CorrelationId' => '?string',
        'DispatchedAt' => '?string', 'CompletedAt' => '?string',
    ],
    'IntegrationProcessingResult' => [
        'EventPublicId' => 'string', 'Dispatches' => 'array', 'Warnings' => 'array',
    ],
    'IntegrationReplayRequest' => [
        'EventPublicId' => 'string', 'Metadata' => 'array',
    ],
    'IntegrationReplayResult' => [
        'EventPublicId' => 'string', 'ReplayEventPublicId' => 'string', 'Status' => 'string',
        'Dispatches' => 'array', 'Metadata' => 'array',
    ],
    'IntegrationDeadLetterRecord' => [
        'PublicId' => 'string', 'Status' => 'string', 'Reason' => 'string',
        'EventPublicId' => '?string', 'DispatchPublicId' => '?string',
        'Payload' => 'array', 'ErrorMessage' => '?string', 'Metadata' => 'array',
        'CreatedAt' => '?string', 'ResolvedAt' => '?string',
    ],
    'IntegrationStatistics' => [
        'Events' => 'int', 'Subscriptions' => 'int', 'Connectors' => 'int',
        'Endpoints' => 'int', 'Dispatches' => 'int', 'DeadLetters' => 'int',
    ],
    'IntegrationHealthReport' => [
        'Enabled' => 'bool', 'Healthy' => 'bool', 'Status' => 'string',
        'Events' => 'int', 'Subscriptions' => 'int', 'Connectors' => 'int',
        'Endpoints' => 'int', 'Dispatches' => 'int', 'DeadLetters' => 'int',
        'MissingTables' => 'array', 'Warnings' => 'array', 'Statistics' => 'array',
    ],
];
foreach ($dtos as $name => $fields) {
    writeFile("{$base}/app/Modules/Sdk/Integration/Data/{$name}.php", dtoClass('App\\Modules\\Sdk\\Integration\\Data', $name, $fields));
    $count++;
}

echo "\nGenerated {$count} SDK files.\n";
