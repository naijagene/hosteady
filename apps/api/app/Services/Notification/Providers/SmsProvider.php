<?php

namespace App\Services\Notification\Providers;

class SmsProvider extends AbstractMetadataNotificationProvider
{
    protected function providerName(): string
    {
        return 'sms';
    }

    protected function supportedChannel(): string
    {
        return 'sms';
    }
}
