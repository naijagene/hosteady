<?php

namespace App\Services\Audit;

use App\Exceptions\Audit\InvalidAuditCursorException;
use App\Support\Tenant\TenantContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class AuditCursorCodec
{
    /**
     * @return array{id: string, occurred_at: string}
     */
    public function decode(TenantContext $context, string $cursor): array
    {
        try {
            /** @var array{organization_id: string, id: string, occurred_at: string}|null $payload */
            $payload = json_decode(Crypt::decryptString($cursor), true);
        } catch (DecryptException) {
            throw new InvalidAuditCursorException;
        }

        if (! is_array($payload)
            || ! isset($payload['organization_id'], $payload['id'], $payload['occurred_at'])
            || $payload['organization_id'] !== $context->organization->id) {
            throw new InvalidAuditCursorException;
        }

        return [
            'id' => (string) $payload['id'],
            'occurred_at' => (string) $payload['occurred_at'],
        ];
    }

    public function encode(TenantContext $context, string $auditLogId, string $occurredAt): string
    {
        return Crypt::encryptString(json_encode([
            'organization_id' => $context->organization->id,
            'id' => $auditLogId,
            'occurred_at' => $occurredAt,
        ]));
    }
}
