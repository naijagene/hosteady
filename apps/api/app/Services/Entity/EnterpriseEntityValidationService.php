<?php

namespace App\Services\Entity;

use App\Modules\Sdk\Entity\Contracts\EntityValidator;
use App\Modules\Sdk\Entity\Data\EntityDefinition;
use App\Modules\Sdk\Entity\Data\EntityMutationRequest;
use App\Modules\Sdk\Entity\Data\EntityValidationIssue;
use App\Modules\Sdk\Entity\Data\EntityValidationReport;
use App\Modules\Sdk\Entity\EnterpriseEntity;
use App\Modules\Sdk\Entity\Enums\EntityValidationSeverity;
use App\Modules\Sdk\Entity\Exceptions\EntityValidationException;

class EnterpriseEntityValidationService implements EntityValidator
{
    /**
     * @param  EntityDefinition|array<string, mixed>|EnterpriseEntity|class-string<EnterpriseEntity>  $source
     */
    public function resolveDefinition(mixed $source): EntityDefinition
    {
        if ($source instanceof EntityDefinition) {
            return $source;
        }

        if ($source instanceof EnterpriseEntity) {
            return $source->toDefinition();
        }

        if (is_array($source)) {
            return EntityDefinition::fromArray($source);
        }

        if (is_string($source) && class_exists($source) && is_subclass_of($source, EnterpriseEntity::class)) {
            /** @var EnterpriseEntity $instance */
            $instance = app($source);

            return $instance->toDefinition();
        }

        throw new EntityValidationException('Unsupported entity definition source.');
    }

    public function validate(EntityDefinition $definition): EntityValidationReport
    {
        $issues = [];

        if ($definition->moduleKey === '') {
            $issues[] = new EntityValidationIssue(
                code: 'missing_module_key',
                message: 'Module key is required.',
                severity: EntityValidationSeverity::Error->value,
                field: 'module_key',
            );
        }

        if ($definition->entityKey === '') {
            $issues[] = new EntityValidationIssue(
                code: 'missing_entity_key',
                message: 'Entity key is required.',
                severity: EntityValidationSeverity::Error->value,
                field: 'entity_key',
            );
        }

        if ($definition->name === '') {
            $issues[] = new EntityValidationIssue(
                code: 'missing_name',
                message: 'Entity name is required.',
                severity: EntityValidationSeverity::Error->value,
                field: 'name',
            );
        }

        if ($definition->moduleKey !== '' && ! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->moduleKey)) {
            $issues[] = new EntityValidationIssue(
                code: 'invalid_module_key',
                message: 'Module key format is invalid.',
                severity: EntityValidationSeverity::Error->value,
                field: 'module_key',
            );
        }

        if ($definition->entityKey !== '' && ! preg_match('/^[a-z0-9]+(?:[._-][a-z0-9]+)*$/', $definition->entityKey)) {
            $issues[] = new EntityValidationIssue(
                code: 'invalid_entity_key',
                message: 'Entity key format is invalid.',
                severity: EntityValidationSeverity::Error->value,
                field: 'entity_key',
            );
        }

        return new EntityValidationReport(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            valid: $issues === [],
            issues: $issues,
        );
    }

    public function validateMutation(
        EntityMutationRequest $request,
        EntityDefinition $definition,
    ): EntityValidationReport {
        $issues = [];

        if ($request->moduleKey !== $definition->moduleKey || $request->entityKey !== $definition->entityKey) {
            $issues[] = new EntityValidationIssue(
                code: 'definition_mismatch',
                message: 'Mutation request does not match the entity definition.',
                severity: EntityValidationSeverity::Error->value,
            );
        }

        if (! in_array($request->operation, ['create', 'update', 'delete', 'restore'], true)) {
            $issues[] = new EntityValidationIssue(
                code: 'invalid_operation',
                message: 'Mutation operation is invalid.',
                severity: EntityValidationSeverity::Error->value,
                field: 'operation',
            );
        }

        if (in_array($request->operation, ['update', 'delete', 'restore'], true) && ($request->entityPublicId === null || $request->entityPublicId === '')) {
            $issues[] = new EntityValidationIssue(
                code: 'missing_entity_public_id',
                message: 'Entity public id is required for this operation.',
                severity: EntityValidationSeverity::Error->value,
                field: 'entity_public_id',
            );
        }

        return new EntityValidationReport(
            moduleKey: $definition->moduleKey,
            entityKey: $definition->entityKey,
            valid: $issues === [],
            issues: $issues,
        );
    }

    public function assertValid(EntityDefinition $definition): void
    {
        $report = $this->validate($definition);

        if (! $report->valid) {
            throw new EntityValidationException(sprintf(
                'Entity definition [%s.%s] is invalid.',
                $definition->moduleKey,
                $definition->entityKey,
            ));
        }
    }
}
