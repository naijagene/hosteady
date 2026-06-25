<?php

namespace App\Exceptions\WorkspaceApplication;

class UnknownWorkspaceSettingKeysException extends WorkspaceApplicationException
{
    /**
     * @param  list<string>  $keys
     */
    public function __construct(array $keys)
    {
        parent::__construct(sprintf(
            'Unknown setting keys: %s.',
            implode(', ', $keys),
        ));
    }
}
