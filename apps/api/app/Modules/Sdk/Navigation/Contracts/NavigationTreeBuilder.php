<?php

namespace App\Modules\Sdk\Navigation\Contracts;

interface NavigationTreeBuilder
{
    /** @param  list<\App\Modules\Sdk\Navigation\Data\NavigationItem>  $items */
    public function build(array $items): \App\Modules\Sdk\Navigation\Data\NavigationTree;
}
