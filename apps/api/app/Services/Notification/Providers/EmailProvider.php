<?php

namespace App\Services\Notification\Providers;

class EmailProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'email';
    }

    protected function supportedChannel(): string
    {
        return 'email';
    }
}
