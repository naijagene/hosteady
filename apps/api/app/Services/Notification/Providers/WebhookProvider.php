<?php

namespace App\Services\Notification\Providers;

class WebhookProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'webhook';
    }

    protected function supportedChannel(): string
    {
        return 'webhook';
    }
}
