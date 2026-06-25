<?php

namespace App\Exceptions\WorkspaceApplication;

class InvalidWorkspaceSettingKeyException extends WorkspaceApplicationException
{
    public function __construct(string $settingKey)
    {
        parent::__construct(sprintf('Invalid setting key [%s].', $settingKey));
    }
}
