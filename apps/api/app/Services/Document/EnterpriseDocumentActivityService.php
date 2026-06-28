<?php

namespace App\Services\Document;

use App\Models\EnterpriseDocumentActivity;
use App\Support\Tenant\TenantContext;
use Illuminate\Support\Str;

class EnterpriseDocumentActivityService
{
    public function __construct(
        private readonly EnterpriseDocumentAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function list(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
    ): array {
        $query = EnterpriseDocumentMapper::applyWorkspaceScope(
            EnterpriseDocumentActivity::query()
                ->where('organization_id', $organizationId)
                ->where('document_public_id', $documentPublicId),
            $workspaceId,
        );

        return $query->orderByDesc('created_at')
            ->get()
            ->map(fn (EnterpriseDocumentActivity $model) => EnterpriseDocumentMapper::toActivityReference($model))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function log(
        string $organizationId,
        ?string $workspaceId,
        string $documentPublicId,
        ?string $enterpriseDocumentId = null,
        string $action = 'activity',
        ?array $beforeState = null,
        ?array $afterState = null,
        ?string $userId = null,
        ?string $membershipId = null,
        array $metadata = [],
    ): array {
        $model = EnterpriseDocumentActivity::query()->create([
            'id' => (string) Str::uuid7(),
            'organization_id' => $organizationId,
            'workspace_id' => $workspaceId,
            'document_public_id' => $documentPublicId,
            'enterprise_document_id' => $enterpriseDocumentId,
            'action' => $action,
            'before_state' => $beforeState,
            'after_state' => $afterState,
            'actor_user_id' => $userId ?? (app()->bound(TenantContext::class) ? app(TenantContext::class)->user->id : null),
            'actor_membership_id' => $membershipId ?? (app()->bound(TenantContext::class) ? app(TenantContext::class)->membership->id : null),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);

        $reference = EnterpriseDocumentMapper::toActivityReference($model);
        $this->auditRecorder->recordActivityLogged($documentPublicId, $action);

        return $reference;
    }
}
