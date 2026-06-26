<?php

namespace App\Services\Enterprise\Workflow\Runtime;

use App\Models\WorkflowInstance;
use App\Modules\Sdk\Workflow\Enums\WorkflowNodeType;
use App\Modules\Sdk\Workflow\Runtime\Contracts\WorkflowExecutionHandler;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionContext;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionReference;
use App\Modules\Sdk\Workflow\Runtime\Data\WorkflowExecutionResult;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowActionStatus;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowExecutionStatus;
use App\Modules\Sdk\Workflow\Runtime\Enums\WorkflowInstanceStatus;
use App\Modules\Sdk\Workflow\Runtime\Exceptions\WorkflowExecutionException;

class WorkflowExecutionEngine
{
    public function __construct(
        private readonly WorkflowExecutionHandler $handler,
        private readonly WorkflowExecutionTracker $tracker,
        private readonly WorkflowExecutionLogger $logger,
        private readonly WorkflowExecutionAuditRecorder $auditRecorder,
    ) {
    }

    /**
     * @param  array<string, mixed>  $definitionJson
     * @param  array<string, mixed>  $variables
     * @return list<WorkflowExecutionReference>
     */
    public function run(
        WorkflowInstance $instance,
        array $definitionJson,
        WorkflowExecutionContext $context,
        array $variables,
        ?string $startNodeId = null,
    ): array {
        $nodes = $this->indexNodes($definitionJson['nodes'] ?? []);
        $transitions = $definitionJson['transitions'] ?? [];

        if ($nodes === []) {
            throw new WorkflowExecutionException('Workflow definition does not contain nodes.');
        }

        $currentNodeId = $startNodeId ?? $this->findStartNodeId($nodes);
        $steps = [];
        $warnings = [];
        $errors = [];
        $maxSteps = 100;
        $stepCount = 0;

        $instance->update([
            'status' => WorkflowInstanceStatus::Running,
            'started_at' => $instance->started_at ?? now(),
            'current_node_id' => $currentNodeId,
        ]);

        while ($currentNodeId !== null && $stepCount < $maxSteps) {
            $stepCount++;
            $node = $nodes[$currentNodeId] ?? null;

            if ($node === null) {
                throw new WorkflowExecutionException(sprintf('Node [%s] was not found.', $currentNodeId));
            }

            $nodeType = (string) ($node['type'] ?? '');
            $step = $this->tracker->startStep($instance, $currentNodeId, $nodeType);
            $this->logger->log($instance, 'info', sprintf('Executing node [%s:%s]', $currentNodeId, $nodeType), $step->id);
            $this->auditRecorder->recordNodeExecuted($instance, $currentNodeId, $nodeType);

            $actionResult = $this->handler->execute($nodeType, $node, $context, $variables);

            if ($actionResult->warnings !== []) {
                $warnings = array_merge($warnings, $actionResult->warnings);
            }

            if ($actionResult->error !== null) {
                $errors[] = $actionResult->error;
            }

            if ($actionResult->status === WorkflowActionStatus::Waiting->value || $nodeType === WorkflowNodeType::Wait->value) {
                $steps[] = $this->tracker->completeStep($step, WorkflowExecutionStatus::Waiting, $actionResult->metadata, $actionResult->warnings, $errors);
                $instance->update([
                    'status' => WorkflowInstanceStatus::Waiting,
                    'current_node_id' => $currentNodeId,
                    'warnings' => array_values(array_unique($warnings)),
                ]);

                return $steps;
            }

            if ($actionResult->status === WorkflowActionStatus::Failed->value) {
                $steps[] = $this->tracker->completeStep($step, WorkflowExecutionStatus::Failed, $actionResult->metadata, $actionResult->warnings, $errors);
                $this->finalizeInstance($instance, WorkflowInstanceStatus::Failed, $warnings, $errors, $steps);

                return $steps;
            }

            $stepStatus = WorkflowExecutionStatus::Completed;
            $steps[] = $this->tracker->completeStep($step, $stepStatus, $actionResult->metadata, $actionResult->warnings, $errors);

            if ($nodeType === WorkflowNodeType::End->value) {
                $this->finalizeInstance($instance, WorkflowInstanceStatus::Completed, $warnings, $errors, $steps, [
                    'completed_node' => $currentNodeId,
                ]);
                $this->auditRecorder->recordCompleted($instance);

                return $steps;
            }

            if ($nodeType === WorkflowNodeType::Parallel->value) {
                $currentNodeId = $this->resolveParallelNextNode($currentNodeId, $transitions, $nodes);
            } elseif ($nodeType === WorkflowNodeType::Condition->value) {
                $selected = $this->selectTransition($currentNodeId, $transitions, $variables, true);
                $this->auditRecorder->recordConditionEvaluated($instance, $currentNodeId, $selected['condition'] ?? null, $selected['to'] ?? null);
                $this->logger->log($instance, 'info', 'Condition evaluated', $step->id, $selected);
                $currentNodeId = $selected['to'] ?? null;
            } else {
                $selected = $this->selectTransition($currentNodeId, $transitions, $variables, false);
                $currentNodeId = $actionResult->nextNodeId ?? ($selected['to'] ?? null);

                if ($currentNodeId === null) {
                    $errors[] = sprintf('No valid transition found from node [%s].', $step->node_id);
                    $this->finalizeInstance($instance, WorkflowInstanceStatus::Failed, $warnings, $errors, $steps);

                    return $steps;
                }
            }

            $instance->update(['current_node_id' => $currentNodeId]);
            $this->tracker->recordEvent($instance, 'node.transition', [
                'from' => $node['id'] ?? $currentNodeId,
                'to' => $currentNodeId,
            ]);
            $this->logger->log($instance, 'info', sprintf('Transitioned to node [%s]', (string) $currentNodeId), $step->id);
        }

        if ($stepCount >= $maxSteps) {
            $errors[] = 'Workflow execution exceeded maximum step count.';
            $this->finalizeInstance($instance, WorkflowInstanceStatus::Failed, $warnings, $errors, $steps);
        }

        return $steps;
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @return array<string, array<string, mixed>>
     */
    private function indexNodes(array $nodes): array
    {
        $indexed = [];

        foreach ($nodes as $node) {
            if (! is_array($node) || ! isset($node['id'])) {
                continue;
            }
            $indexed[(string) $node['id']] = $node;
        }

        return $indexed;
    }

    /**
     * @param  array<string, array<string, mixed>>  $nodes
     */
    private function findStartNodeId(array $nodes): string
    {
        foreach ($nodes as $nodeId => $node) {
            if (($node['type'] ?? null) === WorkflowNodeType::Start->value) {
                return (string) $nodeId;
            }
        }

        throw new WorkflowExecutionException('Workflow definition is missing a start node.');
    }

    /**
     * @param  list<array<string, mixed>>  $transitions
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    private function selectTransition(string $fromNodeId, array $transitions, array $variables, bool $requireCondition): array
    {
        foreach ($transitions as $transition) {
            if (! is_array($transition) || ($transition['from'] ?? null) !== $fromNodeId) {
                continue;
            }

            $condition = $transition['condition'] ?? null;
            if ($requireCondition || ($condition !== null && $condition !== '')) {
                if (! $this->evaluateCondition(is_string($condition) ? $condition : null, $variables)) {
                    continue;
                }
            }

            return $transition;
        }

        return [];
    }

    /**
     * @param  list<array<string, mixed>>  $transitions
     * @param  array<string, array<string, mixed>>  $nodes
     */
    private function resolveParallelNextNode(string $fromNodeId, array $transitions, array $nodes): ?string
    {
        foreach ($transitions as $transition) {
            if (($transition['from'] ?? null) === $fromNodeId) {
                return isset($transition['to']) ? (string) $transition['to'] : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function evaluateCondition(?string $condition, array $variables): bool
    {
        if ($condition === null || $condition === '') {
            return true;
        }

        if (str_contains($condition, '!=')) {
            [$key, $value] = array_map('trim', explode('!=', $condition, 2));

            return (string) ($variables[$key] ?? '') !== $value;
        }

        if (str_contains($condition, '==')) {
            [$key, $value] = array_map('trim', explode('==', $condition, 2));

            return (string) ($variables[$key] ?? '') === $value;
        }

        $this->auditRecorder->recordVariableResolved($condition, $variables[$condition] ?? null);

        return ! empty($variables[trim($condition)]);
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     * @param  list<WorkflowExecutionReference>  $steps
     * @param  array<string, mixed>  $result
     */
    private function finalizeInstance(
        WorkflowInstance $instance,
        WorkflowInstanceStatus $status,
        array $warnings,
        array $errors,
        array $steps,
        array $result = [],
    ): void {
        $completedAt = now();
        $durationMs = $instance->started_at !== null
            ? (int) $instance->started_at->diffInMilliseconds($completedAt)
            : null;

        $instance->update([
            'status' => $status,
            'completed_at' => $completedAt,
            'duration_ms' => $durationMs,
            'warnings' => array_values(array_unique($warnings)),
            'errors' => array_values(array_unique($errors)),
            'result' => $result,
            'current_node_id' => $status === WorkflowInstanceStatus::Completed ? null : $instance->current_node_id,
        ]);

        if ($status === WorkflowInstanceStatus::Failed) {
            $this->auditRecorder->recordFailed($instance);
        }
    }
}
