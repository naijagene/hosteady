<?php

namespace App\Models\Concerns;

trait HasHeosAudit
{
    public function applyAuditActor(?int $userId): static
    {
        if (! $this->exists) {
            $this->created_by_user_id = $userId;
        }

        $this->updated_by_user_id = $userId;

        return $this;
    }

    public function applyDeleteActor(?int $userId): static
    {
        $this->deleted_by_user_id = $userId;

        return $this;
    }
}
