<?php

namespace App\Services\Enterprise\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditActorType;
use App\Enums\AuditEntityType;
use App\Enums\AuditRetentionClass;
use App\Enums\AuditScope;
use App\Enums\AuditSeverity;
use App\Modules\Sdk\Enterprise\Data\ReferenceCatalogData;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;

class EnterpriseReferenceAuditRecorder
{
    public function __construct(
        private readonly AuditEventRecorder $auditEventRecorder,
    ) {
    }

    public function recordCatalogRegistered(ReferenceCatalogData $catalog): void
    {
        try {
            $this->auditEventRecorder->record(new AuditEventData(
                action: AuditAction::ReferenceCatalogRegistered,
                summary: sprintf('Reference catalog %s registered', $catalog->key),
                scope: AuditScope::Platform,
                entityType: AuditEntityType::Application,
                entityPublicId: $catalog->key,
                entityLabel: $catalog->name,
                metadata: [
                    'catalog_key' => $catalog->key,
                    'module_key' => $catalog->moduleKey,
                    'version' => $catalog->version,
                ],
                actorType: AuditActorType::System,
                retentionClass: AuditRetentionClass::Ephemeral,
                severity: AuditSeverity::Info,
            ));
        } catch (\Throwable) {
        }
    }
}
