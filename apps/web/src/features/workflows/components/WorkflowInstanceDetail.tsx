import { useMutation } from '@tanstack/react-query'
import { cancelWorkflowInstance, resumeWorkflowInstance } from '@/api/endpoints/workflows'
import type { WorkflowInstance, WorkflowInstanceHistory } from '@/api/types/workflows'
import { formatWorkflowDate, formatWorkflowDuration, getInstanceDisplayTitle } from '../core/workflow-normalizer'
import { canExecuteWorkflows, canReadWorkflows } from '../core/workflow-permissions'
import { WorkflowErrorState } from './WorkflowErrorState'
import { WorkflowLoadingState } from './WorkflowLoadingState'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'
import { WorkflowTimeline } from './WorkflowTimeline'

interface WorkflowInstanceDetailProps {
  instance: WorkflowInstance | null
  history: WorkflowInstanceHistory | null
  permissions: string[]
  isLoading?: boolean
  error?: { message: string } | null
  onRefresh?: () => Promise<void>
}

export function WorkflowInstanceDetail({
  instance,
  history,
  permissions,
  isLoading,
  error,
  onRefresh,
}: WorkflowInstanceDetailProps) {
  const cancelMutation = useMutation({
    mutationFn: (publicId: string) => cancelWorkflowInstance(publicId),
    onSuccess: () => onRefresh?.(),
  })

  const resumeMutation = useMutation({
    mutationFn: (publicId: string) => resumeWorkflowInstance(publicId),
    onSuccess: () => onRefresh?.(),
  })

  if (isLoading) {
    return <WorkflowLoadingState />
  }

  if (error) {
    return <WorkflowErrorState message={error.message} />
  }

  if (!instance) {
    return <WorkflowErrorState message="Workflow instance not found." />
  }

  const canRead = canReadWorkflows(permissions)
  const canExecute = canExecuteWorkflows(permissions)

  if (!canRead) {
    return <WorkflowErrorState message="You do not have permission to view workflow instances." />
  }

  return (
    <section className="space-y-4" data-testid="workflow-instance-detail">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h2 className="text-lg font-semibold text-foreground">{getInstanceDisplayTitle(instance)}</h2>
          <p className="text-sm text-muted-foreground">{instance.public_id}</p>
        </div>
        <WorkflowStatusBadge status={instance.status} />
      </div>

      <dl className="grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
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
      </dl>

      <section className="rounded-md border border-dashed border-border p-4">
        <h3 className="text-sm font-medium text-foreground">Variables snapshot</h3>
        <p className="mt-2 text-xs text-muted-foreground">Variable snapshot rendering is not available yet.</p>
      </section>

      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1.5 text-xs font-medium"
          onClick={() => onRefresh?.()}
        >
          Refresh
        </button>
        <button
          type="button"
          className="rounded-md border border-destructive px-3 py-1.5 text-xs font-medium text-destructive disabled:opacity-50"
          disabled={!canExecute || cancelMutation.isPending}
          onClick={() => cancelMutation.mutate(instance.public_id)}
        >
          {cancelMutation.isPending ? 'Cancelling…' : 'Cancel'}
        </button>
        <button
          type="button"
          className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
          disabled={!canExecute || resumeMutation.isPending}
          onClick={() => resumeMutation.mutate(instance.public_id)}
        >
          {resumeMutation.isPending ? 'Resuming…' : 'Resume'}
        </button>
      </div>

      <section>
        <h3 className="text-sm font-medium text-foreground">Timeline</h3>
        <div className="mt-3">
          <WorkflowTimeline instanceHistory={history} />
        </div>
      </section>
    </section>
  )
}
