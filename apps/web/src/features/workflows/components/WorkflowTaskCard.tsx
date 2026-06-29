import type { HumanTask } from '@/api/types/workflows'
import { formatWorkflowDate, getTaskDisplayTitle } from '../core/workflow-normalizer'
import { getTaskSlaLabel, isTaskOverdue } from '../core/task-normalizer'
import { WorkflowSlaBadge } from './WorkflowSlaBadge'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'

interface WorkflowTaskCardProps {
  task: HumanTask
  onOpen?: (task: HumanTask) => void
}

export function WorkflowTaskCard({ task, onOpen }: WorkflowTaskCardProps) {
  const slaLabel = getTaskSlaLabel(task)

  return (
    <article
      className="rounded-lg border border-border bg-card p-4"
      data-testid={`workflow-task-card-${task.public_id}`}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="text-sm font-medium text-foreground">{getTaskDisplayTitle(task)}</h3>
          <p className="text-xs text-muted-foreground">{task.task_type}</p>
        </div>
        <WorkflowStatusBadge status={task.status} />
      </div>

      <dl className="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
        <div>
          <dt className="font-medium text-foreground">Priority</dt>
          <dd>{task.priority ?? '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Assignee</dt>
          <dd>{task.assignee_user_public_id || task.assignee_role_key || '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Workflow</dt>
          <dd>{task.workflow_definition_name || task.workflow_instance_public_id || '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Created</dt>
          <dd>{formatWorkflowDate(task.created_at)}</dd>
        </div>
        {typeof task.comments_count === 'number' ? (
          <div>
            <dt className="font-medium text-foreground">Comments</dt>
            <dd>{task.comments_count}</dd>
          </div>
        ) : null}
      </dl>

      {slaLabel ? (
        <div className="mt-3">
          <WorkflowSlaBadge label={slaLabel} overdue={isTaskOverdue(task)} />
        </div>
      ) : null}

      {onOpen ? (
        <button
          type="button"
          className="mt-4 rounded-md border border-border px-3 py-1.5 text-xs font-medium"
          onClick={() => onOpen(task)}
          aria-label={`Open task ${getTaskDisplayTitle(task)}`}
        >
          View task
        </button>
      ) : null}
    </article>
  )
}
