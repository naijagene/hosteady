import type { Approval } from '@/api/types/workflows'
import { formatWorkflowDate } from './workflow-normalizer'

export function getApprovalRequester(approval: Approval): string {
  return (
    (typeof approval.metadata?.requester === 'string' ? approval.metadata.requester : null) ??
    (typeof approval.metadata?.requested_by === 'string' ? approval.metadata.requested_by : null) ??
    '—'
  )
}

export function getApprovalDueLabel(approval: Approval): string | null {
  const dueAt =
    typeof approval.metadata?.due_at === 'string'
      ? approval.metadata.due_at
      : typeof approval.metadata?.dueAt === 'string'
        ? approval.metadata.dueAt
        : null

  return dueAt ? `Due ${formatWorkflowDate(dueAt)}` : null
}
