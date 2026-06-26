<?php

namespace App\Services\Enterprise\Workflow;

use App\Modules\Sdk\Workflow\Contracts\WorkflowValidator;
use App\Modules\Sdk\Workflow\Data\WorkflowDefinitionData;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationIssue;
use App\Modules\Sdk\Workflow\Data\WorkflowValidationReport;
use App\Modules\Sdk\Workflow\Enums\WorkflowNodeType;
use App\Modules\Sdk\Workflow\Enums\WorkflowTriggerType;
use App\Modules\Sdk\Workflow\Enums\WorkflowValidationSeverity;

class WorkflowValidationService implements WorkflowValidator
{
    public function validate(WorkflowDefinitionData $data): WorkflowValidationReport
    {
        $issues = [];

        $this->validateWorkflowKey($data->workflowKey, $issues);
        $this->validateDefinitionPayload($data->toDefinitionJson(), $issues);

        return new WorkflowValidationReport(
            valid: $this->hasNoErrors($issues),
            issues: $issues,
        );
    }

    public function validateDefinitionJson(array $definitionJson, ?string $workflowKey = null): WorkflowValidationReport
    {
        $issues = [];

        if ($workflowKey !== null) {
            $this->validateWorkflowKey($workflowKey, $issues);
        }

        $this->validateDefinitionPayload($definitionJson, $issues);

        return new WorkflowValidationReport(
            valid: $this->hasNoErrors($issues),
            issues: $issues,
        );
    }

    /**
     * @param  list<WorkflowValidationIssue>  $issues
     */
    private function validateWorkflowKey(string $workflowKey, array &$issues): void
    {
        if ($workflowKey === '') {
            $issues[] = new WorkflowValidationIssue(
                code: 'invalid_workflow_key',
                message: 'Workflow key is required.',
                severity: WorkflowValidationSeverity::Error->value,
            );

            return;
        }

        if (! preg_match('/^[a-z][a-z0-9._-]{1,126}$/', $workflowKey)) {
            $issues[] = new WorkflowValidationIssue(
                code: 'invalid_workflow_key',
                message: 'Workflow key must start with a lowercase letter and contain only lowercase letters, numbers, dots, underscores, or dashes.',
                severity: WorkflowValidationSeverity::Error->value,
                path: 'workflow_key',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $definitionJson
     * @param  list<WorkflowValidationIssue>  $issues
     */
    private function validateDefinitionPayload(array $definitionJson, array &$issues): void
    {
        $nodes = is_array($definitionJson['nodes'] ?? null) ? $definitionJson['nodes'] : null;
        $transitions = is_array($definitionJson['transitions'] ?? null) ? $definitionJson['transitions'] : null;
        $triggers = is_array($definitionJson['triggers'] ?? null) ? $definitionJson['triggers'] : null;
        $variables = is_array($definitionJson['variables'] ?? null) ? $definitionJson['variables'] : null;

        if ($nodes === null || $transitions === null || $triggers === null || $variables === null) {
            $issues[] = new WorkflowValidationIssue(
                code: 'invalid_version_payload',
                message: 'Definition payload must include nodes, transitions, triggers, and variables arrays.',
                severity: WorkflowValidationSeverity::Error->value,
            );

            return;
        }

        $nodeIds = [];
        $startCount = 0;
        $endCount = 0;

        foreach ($nodes as $index => $node) {
            if (! is_array($node)) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'invalid_version_payload',
                    message: sprintf('Node at index %d must be an object.', $index),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "nodes.$index",
                );

                continue;
            }

            $nodeId = (string) ($node['id'] ?? '');
            $nodeType = (string) ($node['type'] ?? '');

            if ($nodeId === '') {
                $issues[] = new WorkflowValidationIssue(
                    code: 'missing_node_id',
                    message: 'Each node must have an id.',
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "nodes.$index.id",
                );
            } elseif (isset($nodeIds[$nodeId])) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'duplicate_node_id',
                    message: sprintf('Duplicate node id "%s".', $nodeId),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "nodes.$index.id",
                );
            } else {
                $nodeIds[$nodeId] = true;
            }

            if ($nodeType === '' || ! in_array($nodeType, WorkflowNodeType::values(), true)) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'invalid_node_type',
                    message: sprintf('Invalid node type "%s".', $nodeType),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "nodes.$index.type",
                );
            } elseif ($nodeType === WorkflowNodeType::Start->value) {
                $startCount++;
            } elseif ($nodeType === WorkflowNodeType::End->value) {
                $endCount++;
            }
        }

        if ($startCount === 0) {
            $issues[] = new WorkflowValidationIssue(
                code: 'missing_start_node',
                message: 'Workflow must contain at least one start node.',
                severity: WorkflowValidationSeverity::Error->value,
            );
        }

        if ($endCount === 0) {
            $issues[] = new WorkflowValidationIssue(
                code: 'missing_end_node',
                message: 'Workflow must contain at least one end node.',
                severity: WorkflowValidationSeverity::Error->value,
            );
        }

        $transitionKeys = [];

        foreach ($transitions as $index => $transition) {
            if (! is_array($transition)) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'invalid_version_payload',
                    message: sprintf('Transition at index %d must be an object.', $index),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "transitions.$index",
                );

                continue;
            }

            $transitionId = (string) ($transition['id'] ?? '');
            $from = (string) ($transition['from'] ?? '');
            $to = (string) ($transition['to'] ?? '');

            if ($transitionId === '' || $from === '' || $to === '') {
                $issues[] = new WorkflowValidationIssue(
                    code: 'broken_transition',
                    message: 'Each transition must include id, from, and to.',
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "transitions.$index",
                );
            }

            $signature = $transitionId.'|'.$from.'|'.$to;
            if (isset($transitionKeys[$signature])) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'duplicate_transition',
                    message: sprintf('Duplicate transition "%s".', $transitionId),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "transitions.$index",
                );
            } else {
                $transitionKeys[$signature] = true;
            }

            if ($from !== '' && ! isset($nodeIds[$from])) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'missing_transition_target',
                    message: sprintf('Transition from node "%s" references unknown node.', $from),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "transitions.$index.from",
                );
            }

            if ($to !== '' && ! isset($nodeIds[$to])) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'missing_transition_target',
                    message: sprintf('Transition to node "%s" references unknown node.', $to),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "transitions.$index.to",
                );
            }
        }

        $variableKeys = [];
        foreach ($variables as $index => $variable) {
            if (! is_array($variable)) {
                continue;
            }

            $key = (string) ($variable['key'] ?? $variable['variable_key'] ?? '');
            if ($key === '') {
                $issues[] = new WorkflowValidationIssue(
                    code: 'duplicate_variable',
                    message: 'Each variable must have a key.',
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "variables.$index.key",
                );

                continue;
            }

            if (isset($variableKeys[$key])) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'duplicate_variable',
                    message: sprintf('Duplicate variable key "%s".', $key),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "variables.$index.key",
                );
            } else {
                $variableKeys[$key] = true;
            }
        }

        foreach ($triggers as $index => $trigger) {
            if (! is_array($trigger)) {
                continue;
            }

            $type = (string) ($trigger['type'] ?? '');
            if ($type === '' || ! in_array($type, WorkflowTriggerType::values(), true)) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'invalid_trigger_type',
                    message: sprintf('Invalid trigger type "%s".', $type),
                    severity: WorkflowValidationSeverity::Error->value,
                    path: "triggers.$index.type",
                );
            }
        }

        $this->detectUnreachableNodes($nodes, $transitions, $issues);
        $this->detectCycles($nodes, $transitions, $issues);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array<string, mixed>>  $transitions
     * @param  list<WorkflowValidationIssue>  $issues
     */
    private function detectUnreachableNodes(array $nodes, array $transitions, array &$issues): void
    {
        $startIds = [];
        foreach ($nodes as $node) {
            if (($node['type'] ?? null) === WorkflowNodeType::Start->value) {
                $startIds[] = (string) $node['id'];
            }
        }

        if ($startIds === []) {
            return;
        }

        $adjacency = [];
        foreach ($transitions as $transition) {
            $from = (string) ($transition['from'] ?? '');
            $to = (string) ($transition['to'] ?? '');
            if ($from === '' || $to === '') {
                continue;
            }
            $adjacency[$from][] = $to;
        }

        $reachable = [];
        $queue = $startIds;
        while ($queue !== []) {
            $current = array_shift($queue);
            if (isset($reachable[$current])) {
                continue;
            }
            $reachable[$current] = true;
            foreach ($adjacency[$current] ?? [] as $next) {
                if (! isset($reachable[$next])) {
                    $queue[] = $next;
                }
            }
        }

        foreach ($nodes as $node) {
            $nodeId = (string) ($node['id'] ?? '');
            if ($nodeId !== '' && ! isset($reachable[$nodeId])) {
                $issues[] = new WorkflowValidationIssue(
                    code: 'unreachable_node',
                    message: sprintf('Node "%s" is unreachable from start.', $nodeId),
                    severity: WorkflowValidationSeverity::Warning->value,
                    path: 'nodes',
                );
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  list<array<string, mixed>>  $transitions
     * @param  list<WorkflowValidationIssue>  $issues
     */
    private function detectCycles(array $nodes, array $transitions, array &$issues): void
    {
        $adjacency = [];
        foreach ($nodes as $node) {
            $adjacency[(string) $node['id']] = [];
        }

        foreach ($transitions as $transition) {
            $from = (string) ($transition['from'] ?? '');
            $to = (string) ($transition['to'] ?? '');
            if ($from === '' || $to === '' || ! isset($adjacency[$from])) {
                continue;
            }
            $adjacency[$from][] = $to;
        }

        $visited = [];
        $stack = [];

        $visit = function (string $node) use (&$visit, &$visited, &$stack, $adjacency, &$issues): void {
            $visited[$node] = true;
            $stack[$node] = true;

            foreach ($adjacency[$node] ?? [] as $next) {
                if (! isset($visited[$next])) {
                    $visit($next);
                } elseif (isset($stack[$next])) {
                    $issues[] = new WorkflowValidationIssue(
                        code: 'cycle_detected',
                        message: sprintf('Cycle detected involving node "%s".', $next),
                        severity: WorkflowValidationSeverity::Error->value,
                        path: 'transitions',
                    );

                    return;
                }
            }

            unset($stack[$node]);
        };

        foreach (array_keys($adjacency) as $nodeId) {
            if (! isset($visited[$nodeId])) {
                $visit($nodeId);
            }
        }
    }

    /**
     * @param  list<WorkflowValidationIssue>  $issues
     */
    private function hasNoErrors(array $issues): bool
    {
        foreach ($issues as $issue) {
            if ($issue->severity === WorkflowValidationSeverity::Error->value) {
                return false;
            }
        }

        return true;
    }
}
