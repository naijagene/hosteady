<?php

namespace App\Modules\Sdk\Entity\Contracts;

use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityLifecycleEvent;

interface EntitySearchProvider
{
    public function moduleKey(): string;

    public function entityKey(): string;

    public function indexDefinition(EntityDefinition $definition): void;

    public function indexLifecycleEvent(EntityLifecycleEvent $event): void;

    public function indexComment(string $entityPublicId, array $comment): void;

    public function indexTag(string $entityPublicId, array $tag): void;

    public function remove(string $entityPublicId): void;
}
