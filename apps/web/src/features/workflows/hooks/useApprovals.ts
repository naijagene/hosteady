import { useQuery } from '@tanstack/react-query'
import { fetchApproval, fetchApprovals } from '@/api/endpoints/workflows'
import type { WorkflowQueryPayload } from '@/api/types/workflows'
import { createInitialWorkflowQuery } from '../core/workflow-query'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useApprovals(payload?: WorkflowQueryPayload) {
  const queryPayload = createInitialWorkflowQuery(payload)

  const query = useQuery({
    queryKey: ['approvals', queryPayload],
    queryFn: () => fetchApprovals(queryPayload),
  })

  return {
    query,
    approvals: query.data ?? [],
    error: query.error ? toWorkflowQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}

export function useApproval(publicId?: string | null) {
  const query = useQuery({
    queryKey: ['approval', publicId],
    queryFn: () => fetchApproval(publicId!),
    enabled: Boolean(publicId),
  })

  return {
    query,
    approval: query.data ?? null,
    error: query.error ? toWorkflowQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
