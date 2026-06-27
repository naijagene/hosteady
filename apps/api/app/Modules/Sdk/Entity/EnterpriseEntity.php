<?php

namespace App\Modules\Sdk\Entity;

use App\Modules\Sdk\Entity\Contracts\EnterpriseEntityContract;
use App\Modules\Sdk\Entity\Contracts\EntityLifecycleHandler;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;
use App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition;
use App\Modules\Sdk\Entity\Data\EntityValidationRule;
use App\Modules\Sdk\Entity\Enums\EntityOwnershipScope;
use App\Modules\Sdk\Entity\Enums\EntityStatus;
use App\Modules\Sdk\Entity\Enums\EntityVisibility;
use Illuminate\Support\Str;

/**
 * Convention-based base class for HEOS enterprise entities.
 *
 * Extend with explicit metadata overrides:
 *
 * class Product extends EnterpriseEntity
 * {
 *     protected string $entityKey = 'product';
 *     protected string $entityLabel = 'Product';
 *     protected string $moduleKey = 'barsoft';
 * }
 *
 * This class is metadata-only and does not access the database.
 */
abstract class EnterpriseEntity implements EnterpriseEntityContract
{
    protected string $entityKey = '';

    protected string $entityLabel = '';

    protected string $moduleKey = '';

    protected ?string $entityDescription = null;

    protected ?string $entityIcon = null;

    protected bool $searchable = true;

    protected bool $auditable = true;

    protected bool $workflowEnabled = true;

    protected bool $attachmentsEnabled = false;

    protected bool $commentsEnabled = true;

    protected bool $tagsEnabled = true;

    protected ?string $tableName = null;

    protected string $status = EntityStatus::Registered->value;

    protected string $visibility = EntityVisibility::Organization->value;

    protected string $ownershipScope = EntityOwnershipScope::Organization->value;

    /**
     * @var array<string, mixed>
     */
    protected array $metadata = [];

    public function entityKey(): string
    {
        if ($this->entityKey !== '') {
            return $this->entityKey;
        }

        return Str::snake(class_basename(static::class));
    }

    public function entityLabel(): string
    {
        if ($this->entityLabel !== '') {
            return $this->entityLabel;
        }

        return Str::headline(class_basename(static::class));
    }

    public function moduleKey(): string
    {
        return $this->moduleKey;
    }

    public function entityDescription(): ?string
    {
        return $this->entityDescription;
    }

    public function entityIcon(): ?string
    {
        return $this->entityIcon;
    }

    public function searchable(): bool
    {
        return $this->searchable;
    }

    public function auditable(): bool
    {
        return $this->auditable;
    }

    public function workflowEnabled(): bool
    {
        return $this->workflowEnabled;
    }

    public function attachmentsEnabled(): bool
    {
        return $this->attachmentsEnabled;
    }

    public function commentsEnabled(): bool
    {
        return $this->commentsEnabled;
    }

    public function tagsEnabled(): bool
    {
        return $this->tagsEnabled;
    }

    /**
     * @return list<EntityFieldDefinition>
     */
    public function fields(): array
    {
        return [];
    }

    /**
     * @return list<EntityRelationshipDefinition>
     */
    public function relationships(): array
    {
        return [];
    }

    /**
     * @return list<EntityValidationRule>
     */
    public function validationRules(): array
    {
        return [];
    }

    /**
     * @return list<class-string<EntityLifecycleHandler>|EntityLifecycleHandler>
     */
    public function lifecycleHandlers(): array
    {
        return [];
    }

    public function toDefinition(): EntityDefinition
    {
        return new EntityDefinition(
            moduleKey: $this->moduleKey(),
            entityKey: $this->entityKey(),
            name: $this->entityLabel(),
            description: $this->entityDescription(),
            icon: $this->entityIcon(),
            status: $this->status,
            visibility: $this->visibility,
            ownershipScope: $this->ownershipScope,
            tableName: $this->tableName,
            className: static::class,
            capabilities: $this->buildCapabilities(),
            fields: $this->fields(),
            relationships: $this->relationships(),
            validationRules: $this->validationRules(),
            metadata: $this->metadata,
        );
    }

    /**
     * @return array<string, bool>
     */
    protected function buildCapabilities(): array
    {
        return [
            'searchable' => $this->searchable(),
            'auditable' => $this->auditable(),
            'workflow_enabled' => $this->workflowEnabled(),
            'attachments_enabled' => $this->attachmentsEnabled(),
            'comments_enabled' => $this->commentsEnabled(),
            'tags_enabled' => $this->tagsEnabled(),
        ];
    }
}
