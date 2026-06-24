<?php

namespace App\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Enums\OrganizationStatus;
use App\Enums\WorkspaceStatus;
use App\Enums\AuditAction;
use App\Enums\AuditEntityType;
use App\Exceptions\Tenant\TenantContextException;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Workspace;
use App\Services\Audit\AuditEventRecorder;
use App\Services\Audit\Data\AuditEventData;
use App\Support\Tenant\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    public const ORGANIZATION_HEADER = 'X-HEOS-Organization-Id';

    public const WORKSPACE_HEADER = 'X-HEOS-Workspace-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        $organizationPublicId = $request->header(self::ORGANIZATION_HEADER);

        if (! is_string($organizationPublicId) || $organizationPublicId === '') {
            throw new TenantContextException(
                'The X-HEOS-Organization-Id header is required.',
                422,
            );
        }

        if (! $this->isUuid($organizationPublicId)) {
            throw new TenantContextException(
                'The X-HEOS-Organization-Id header must be a valid UUID.',
                422,
            );
        }

        $organization = Organization::query()
            ->where('public_id', $organizationPublicId)
            ->whereNull('deleted_at')
            ->first();

        if ($organization === null) {
            throw new TenantContextException('Organization not found.', 404);
        }

        if ($organization->status !== OrganizationStatus::Active) {
            throw new TenantContextException('Organization is not active.', 403);
        }

        $membership = OrganizationMembership::query()
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('status', MembershipStatus::Active)
            ->whereNull('deleted_at')
            ->first();

        if ($membership === null) {
            throw new TenantContextException('You do not have an active membership for this organization.', 403);
        }

        $workspacePublicId = $request->header(self::WORKSPACE_HEADER);

        if (is_string($workspacePublicId) && $workspacePublicId !== '') {
            if (! $this->isUuid($workspacePublicId)) {
                throw new TenantContextException(
                    'The X-HEOS-Workspace-Id header must be a valid UUID.',
                    422,
                );
            }

            $workspace = Workspace::query()
                ->where('public_id', $workspacePublicId)
                ->where('organization_id', $organization->id)
                ->where('status', WorkspaceStatus::Active)
                ->whereNull('deleted_at')
                ->first();

            if ($workspace === null) {
                throw new TenantContextException('Workspace not found for this organization.', 404);
            }
        } else {
            $workspace = Workspace::query()
                ->whereKey($membership->default_workspace_id)
                ->where('organization_id', $organization->id)
                ->where('status', WorkspaceStatus::Active)
                ->whereNull('deleted_at')
                ->first();

            if ($workspace === null) {
                throw new TenantContextException('Default workspace is unavailable.', 403);
            }
        }

        $context = TenantContext::fromModels($user, $organization, $membership, $workspace);

        app()->instance(TenantContext::class, $context);
        $request->attributes->set('tenantContext', $context);

        app(AuditEventRecorder::class)->record(new AuditEventData(
            action: AuditAction::TenantContextSelected,
            summary: sprintf('Tenant context selected for %s', $organization->name),
            entityType: AuditEntityType::Organization,
            entityPublicId: $organization->public_id,
            entityLabel: $organization->name,
            metadata: [
                'organization_public_id' => $organization->public_id,
                'workspace_public_id' => $workspace->public_id,
                'membership_public_id' => $membership->public_id,
            ],
        ));

        return $next($request);
    }

    private function isUuid(string $value): bool
    {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value,
        ) === 1;
    }
}
