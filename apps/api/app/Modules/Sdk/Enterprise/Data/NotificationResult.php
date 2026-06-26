<?php

namespace App\Modules\Sdk\Enterprise\Data;

readonly class NotificationResult
{
    /**
     * @param  list<string>  $deliveredChannels
     */
    public function __construct(
        public string $notificationPublicId,
        public array $deliveredChannels,
        public string $status,
    ) {
    }
}
