<?php

namespace App\Support;

use App\Exceptions\Organization\OrganizationProvisioningException;
use App\Models\Invitation;
use App\Models\Organization;
use Illuminate\Database\QueryException;

class CodeGenerator
{
    private const ORGANIZATION_PREFIX = 'ORG-';

    private const INVITATION_PREFIX = 'INV-';

    private const MAX_ATTEMPTS = 5;

    public function organizationCode(): string
    {
        return $this->generateSequentialCode(
            Organization::class,
            'organization_code',
            self::ORGANIZATION_PREFIX,
        );
    }

    public function invitationCode(): string
    {
        return $this->generateSequentialCode(
            Invitation::class,
            'invitation_code',
            self::INVITATION_PREFIX,
        );
    }

    /**
     * @param  class-string  $modelClass
     */
    private function generateSequentialCode(string $modelClass, string $column, string $prefix): string
    {
        $lastSequence = $modelClass::query()
            ->where($column, 'like', $prefix.'%')
            ->orderByDesc($column)
            ->value($column);

        $nextSequence = 1;

        if (is_string($lastSequence) && preg_match('/^'.preg_quote($prefix, '/').'(\d{6})$/', $lastSequence, $matches)) {
            $nextSequence = (int) $matches[1] + 1;
        }

        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = sprintf('%s%06d', $prefix, $nextSequence + $attempt);

            if (! $modelClass::query()->where($column, $code)->exists()) {
                return $code;
            }
        }

        throw new OrganizationProvisioningException('Unable to generate a unique operational code.');
    }

    public function organizationCodeWithRetry(callable $persist): string
    {
        return $this->persistWithRetry(fn () => $this->organizationCode(), $persist);
    }

    public function invitationCodeWithRetry(callable $persist): string
    {
        return $this->persistWithRetry(fn () => $this->invitationCode(), $persist);
    }

    private function persistWithRetry(callable $generator, callable $persist): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; $attempt++) {
            $code = $generator();

            try {
                $persist($code);

                return $code;
            } catch (QueryException) {
                continue;
            }
        }

        throw new OrganizationProvisioningException('Unable to persist a unique operational code.');
    }
}
