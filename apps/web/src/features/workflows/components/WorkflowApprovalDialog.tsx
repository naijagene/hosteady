import { useState } from 'react'
import type { Approval } from '@/api/types/workflows'
import { getApprovalDisplayTitle } from '../core/workflow-normalizer'
import { useApprovalActions } from '../hooks/useApprovalActions'
import { canDecideApprovals } from '../core/workflow-permissions'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'

interface WorkflowApprovalDialogProps {
  approval: Approval | null
  open: boolean
  permissions: string[]
  onClose: () => void
}

export function WorkflowApprovalDialog({
  approval,
  open,
  permissions,
  onClose,
}: WorkflowApprovalDialogProps) {
  const [comment, setComment] = useState('')
  const [successMessage, setSuccessMessage] = useState<string | null>(null)
  const actions = useApprovalActions()
  const canDecide = canDecideApprovals(permissions)

  if (!open || !approval) {
    return null
  }

  const handleApprove = async () => {
    setSuccessMessage(null)
    await actions.approve({ publicId: approval.public_id, payload: { comment } })
    setSuccessMessage('Approval submitted.')
    setComment('')
  }

  const handleReject = async () => {
    setSuccessMessage(null)
    await actions.reject({ publicId: approval.public_id, payload: { comment } })
    setSuccessMessage('Rejection submitted.')
    setComment('')
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
      data-testid="workflow-approval-dialog"
      role="dialog"
      aria-modal="true"
      aria-label={`Approval dialog ${getApprovalDisplayTitle(approval)}`}
    >
      <div className="w-full max-w-lg rounded-lg border border-border bg-background p-5">
        <div className="flex items-start justify-between gap-3">
          <div>
            <h2 className="text-lg font-semibold text-foreground">{getApprovalDisplayTitle(approval)}</h2>
            <p className="text-sm text-muted-foreground">{approval.approval_type}</p>
          </div>
          <WorkflowStatusBadge status={approval.status} />
        </div>

        <label htmlFor="approval-decision-comment" className="mt-4 block text-xs font-medium text-foreground">
          Decision note
        </label>
        <textarea
          id="approval-decision-comment"
          className="mt-2 min-h-24 w-full rounded-md border border-border bg-background px-3 py-2 text-sm"
          value={comment}
          onChange={(event) => setComment(event.target.value)}
          aria-label="Approval decision comment"
        />

        {(actions.approveError ?? actions.rejectError) ? (
          <p className="mt-2 text-xs text-destructive" role="alert">
            {(actions.approveError ?? actions.rejectError)?.message}
          </p>
        ) : null}

        {successMessage ? (
          <p className="mt-2 text-xs text-emerald-700" role="status">
            {successMessage}
          </p>
        ) : null}

        <div className="mt-4 flex flex-wrap gap-2">
          <button
            type="button"
            className="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground disabled:opacity-50"
            disabled={!canDecide || actions.isApproving}
            onClick={handleApprove}
            aria-busy={actions.isApproving}
          >
            {actions.isApproving ? 'Approving…' : 'Approve'}
          </button>
          <button
            type="button"
            className="rounded-md border border-destructive px-3 py-1.5 text-xs font-medium text-destructive disabled:opacity-50"
            disabled={!canDecide || actions.isRejecting}
            onClick={handleReject}
            aria-busy={actions.isRejecting}
          >
            {actions.isRejecting ? 'Rejecting…' : 'Reject'}
          </button>
          <button type="button" className="rounded-md border border-border px-3 py-1.5 text-xs font-medium" onClick={onClose}>
            Close
          </button>
        </div>
      </div>
    </div>
  )
}
