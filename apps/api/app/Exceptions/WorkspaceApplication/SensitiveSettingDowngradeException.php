<?php

namespace App\Exceptions\WorkspaceApplication;

class SensitiveSettingDowngradeException extends WorkspaceApplicationException
{
    public function __construct(string $settingKey)
    {
        parent::__construct(sprintf(
            'Setting [%s] cannot be changed from sensitive to non-sensitive.',
            $settingKey,
        ));
    }
}
