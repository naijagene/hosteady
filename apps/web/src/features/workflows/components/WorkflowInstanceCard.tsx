import type { WorkflowInstance } from '@/api/types/workflows'
import { formatWorkflowDate, formatWorkflowDuration, getInstanceDisplayTitle } from '../core/workflow-normalizer'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'

interface WorkflowInstanceCardProps {
  instance: WorkflowInstance
  onOpen?: (instance: WorkflowInstance) => void
}

export function WorkflowInstanceCard({ instance, onOpen }: WorkflowInstanceCardProps) {
  const warningCount = instance.warnings?.length ?? 0
  const errorCount = instance.errors?.length ?? 0

  return (
    <article
      className="rounded-lg border border-border bg-card p-4"
      data-testid={`workflow-instance-card-${instance.public_id}`}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="text-sm font-medium text-foreground">{getInstanceDisplayTitle(instance)}</h3>
          <p className="text-xs text-muted-foreground">{instance.workflow_key ?? instance.public_id}</p>
        </div>
        <WorkflowStatusBadge status={instance.status} />
      </div>

      <dl className="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
        <div>
          <dt className="font-medium text-foreground">Current node</dt>
          <dd>{instance.current_node_id ?? '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Started</dt>
          <dd>{formatWorkflowDate(instance.started_at)}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Completed</dt>
          <dd>{formatWorkflowDate(instance.completed_at)}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Duration</dt>
          <dd>{formatWorkflowDuration(instance.duration_ms)}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Warnings / errors</dt>
          <dd>
            {warningCount} / {errorCount}
          </dd>
        </div>
      </dl>

      {onOpen ? (
        <button
          type="button"
          className="mt-4 rounded-md border border-border px-3 py-1.5 text-xs font-medium"
          onClick={() => onOpen(instance)}
          aria-label={`Open workflow instance ${getInstanceDisplayTitle(instance)}`}
        >
          View instance
        </button>
      ) : null}
    </article>
  )
}
