import { useParams } from '@tanstack/react-router'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { WorkflowApprovalDialog } from '../components/WorkflowApprovalDialog'
import { WorkflowErrorState } from '../components/WorkflowErrorState'
import { WorkflowLoadingState } from '../components/WorkflowLoadingState'
import { useApproval } from '../hooks/useApprovals'

export function ApprovalPage() {
  const params = useParams({ strict: false })
  const approvalPublicId = typeof params.approvalPublicId === 'string' ? params.approvalPublicId : null
  const runtime = useHydratedRuntime()
  const { approval, query, error } = useApproval(approvalPublicId)

  if (query.isLoading) {
    return <WorkflowLoadingState />
  }

  if (error) {
    return <WorkflowErrorState message={error.message} />
  }

  return (
    <WorkflowApprovalDialog
      approval={approval}
      open={Boolean(approval)}
      permissions={runtime?.permissions ?? []}
      onClose={() => undefined}
    />
  )
}
