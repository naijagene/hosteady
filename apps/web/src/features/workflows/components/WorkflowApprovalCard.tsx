import type { Approval } from '@/api/types/workflows'
import { formatWorkflowDate, getApprovalDisplayTitle } from '../core/workflow-normalizer'
import { getApprovalDueLabel, getApprovalRequester } from '../core/approval-normalizer'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'

interface WorkflowApprovalCardProps {
  approval: Approval
  onOpen?: (approval: Approval) => void
}

export function WorkflowApprovalCard({ approval, onOpen }: WorkflowApprovalCardProps) {
  const dueLabel = getApprovalDueLabel(approval)

  return (
    <article
      className="rounded-lg border border-border bg-card p-4"
      data-testid={`workflow-approval-card-${approval.public_id}`}
    >
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="text-sm font-medium text-foreground">{getApprovalDisplayTitle(approval)}</h3>
          <p className="text-xs text-muted-foreground">{approval.approval_type}</p>
        </div>
        <WorkflowStatusBadge status={approval.status} />
      </div>

      <dl className="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
        <div>
          <dt className="font-medium text-foreground">Requester</dt>
          <dd>{getApprovalRequester(approval)}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Workflow</dt>
          <dd>{approval.workflow_definition_name || approval.workflow_instance_public_id || '—'}</dd>
        </div>
        <div>
          <dt className="font-medium text-foreground">Created</dt>
          <dd>{formatWorkflowDate(approval.requested_at)}</dd>
        </div>
        {dueLabel ? (
          <div>
            <dt className="font-medium text-foreground">Due</dt>
            <dd>{dueLabel}</dd>
          </div>
        ) : null}
      </dl>

      {onOpen ? (
        <button
          type="button"
          className="mt-4 rounded-md border border-border px-3 py-1.5 text-xs font-medium"
          onClick={() => onOpen(approval)}
          aria-label={`Review approval ${getApprovalDisplayTitle(approval)}`}
        >
          Review
        </button>
      ) : null}
    </article>
  )
}
