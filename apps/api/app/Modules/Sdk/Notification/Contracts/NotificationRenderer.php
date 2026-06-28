<?php

namespace App\Modules\Sdk\Notification\Contracts;

use App\Modules\Sdk\Notification\Data\NotificationTemplate;

/**
 * Renders notification templates by merging variable data into title and body content.
 */
interface NotificationRenderer
{
    /**
     * @param array<string, mixed> $mergeData
     *
     * @return array{title: string, body: string}
     */
    public function render(NotificationTemplate $template, array $mergeData): array;
}
