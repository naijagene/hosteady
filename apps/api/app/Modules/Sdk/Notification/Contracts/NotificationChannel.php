<?php

namespace App\Modules\Sdk\Notification\Contracts;

/**
 * Declares a named delivery channel and its supported channel identifiers.
 */
interface NotificationChannel
{
    /**
     * Determine whether this channel implementation handles the given channel key.
     */
    public function supports(string $channel): bool;

    /**
     * Return the canonical channel name for this implementation.
     */
    public function name(): string;
}
