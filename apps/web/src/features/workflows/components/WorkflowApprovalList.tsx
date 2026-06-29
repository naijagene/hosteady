import type { Approval } from '@/api/types/workflows'
import { WorkflowEmptyState } from './WorkflowEmptyState'
import { WorkflowApprovalCard } from './WorkflowApprovalCard'

interface WorkflowApprovalListProps {
  approvals: Approval[]
  onOpen?: (approval: Approval) => void
  emptyMessage?: string
}

export function WorkflowApprovalList({ approvals, onOpen, emptyMessage }: WorkflowApprovalListProps) {
  if (approvals.length === 0) {
    return <WorkflowEmptyState message={emptyMessage} />
  }

  return (
    <div className="grid gap-3" data-testid="workflow-approval-list">
      {approvals.map((approval) => (
        <WorkflowApprovalCard key={approval.public_id} approval={approval} onOpen={onOpen} />
      ))}
    </div>
  )
}
