<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\AuditLog
 */
class AuditEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'category' => $this->category->value ?? $this->category,
            'action' => $this->action->value ?? $this->action,
            'event_version' => $this->event_version,
            'severity' => $this->severity->value ?? $this->severity,
            'summary' => $this->summary,
            'actor' => [
                'type' => $this->actor_type->value ?? $this->actor_type,
                'user_public_id' => $this->actorUser?->public_id,
                'membership_public_id' => $this->actorMembership?->public_id,
                'display_name' => $this->actorUser?->display_name ?? $this->actorUser?->name,
            ],
            'organization_public_id' => $this->organization?->public_id,
            'workspace_public_id' => $this->workspace?->public_id,
            'entity' => $this->entity_type !== null ? [
                'type' => $this->entity_type,
                'public_id' => $this->entity_public_id,
                'label' => $this->entity_label,
            ] : null,
            'changes' => [
                'before' => $this->before_state,
                'after' => $this->after_state,
            ],
            'metadata' => $this->metadata,
            'context' => [
                'request_id' => $this->request_id,
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
            ],
            'retention_class' => $this->retention_class->value ?? $this->retention_class,
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}
