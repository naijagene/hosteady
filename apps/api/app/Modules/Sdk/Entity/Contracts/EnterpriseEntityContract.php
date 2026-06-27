<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityFieldDefinition;
use App\Modules\Sdk\Entity\Data\EntityRelationshipDefinition;
use App\Modules\Sdk\Entity\Data\EntityValidationRule;

interface EnterpriseEntityContract
{
    public function entityKey(): string;

    public function entityLabel(): string;

    public function moduleKey(): string;

    public function entityDescription(): ?string;

    public function entityIcon(): ?string;

    public function searchable(): bool;

    public function auditable(): bool;

    public function workflowEnabled(): bool;

    public function attachmentsEnabled(): bool;

    public function commentsEnabled(): bool;

    public function tagsEnabled(): bool;

    /**
     * @return list<EntityFieldDefinition>
     */
    public function fields(): array;

    /**
     * @return list<EntityRelationshipDefinition>
     */
    public function relationships(): array;

    /**
     * @return list<EntityValidationRule>
     */
    public function validationRules(): array;

    /**
     * @return list<class-string<EntityLifecycleHandler>|EntityLifecycleHandler>
     */
    public function lifecycleHandlers(): array;

    public function toDefinition(): EntityDefinition;
}
