<?php

namespace App\Modules\Sdk\Exceptions;

use App\Modules\Sdk\Data\LifecycleResult;
use RuntimeException;

class LifecycleException extends RuntimeException
{
    public function __construct(
        public readonly LifecycleResult $result,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf(
                'Module lifecycle failed for "%s" during "%s".',
                $result->moduleKey,
                $result->operation->value,
            ),
            0,
            $previous,
        );
    }
}
