<?php

namespace App\Services\Ui;

use App\Modules\Sdk\Ui\Contracts\UiActionProvider;
use App\Modules\Sdk\Ui\Data\UiComponent;
use App\Modules\Sdk\Ui\Data\UiPageDefinition;
use App\Support\Tenant\TenantContext;

class UiActionService implements UiActionProvider
{
    public function __construct(
        private readonly UiConditionEvaluatorService $conditionEvaluator,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function pageActions(UiPageDefinition $page): array
    {
        return $this->filterActions($page->actions);
    }

    /** @return list<array<string, mixed>> */
    public function componentActions(UiComponent $component): array
    {
        return $this->filterActions($component->actions);
    }

    /**
     * @param  list<array<string, mixed>>  $actions
     * @return list<array<string, mixed>>
     */
    private function filterActions(array $actions): array
    {
        if (! app()->bound(TenantContext::class)) {
            return $actions;
        }

        $context = app(TenantContext::class);
        $visible = [];

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $conditions = is_array($action['conditions'] ?? null) ? $action['conditions'] : [];

            if ($this->conditionEvaluator->evaluate($context, $conditions)) {
                $visible[] = $action;
            }
        }

        return $visible;
    }
}
