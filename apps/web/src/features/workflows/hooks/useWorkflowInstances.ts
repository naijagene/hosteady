import { useQuery } from '@tanstack/react-query'
import {
  fetchWorkflowInstance,
  fetchWorkflowInstanceHistory,
  fetchWorkflowInstances,
} from '@/api/endpoints/workflows'
import type { WorkflowQueryPayload } from '@/api/types/workflows'
import { createInitialWorkflowQuery } from '../core/workflow-query'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useWorkflowInstances(payload?: WorkflowQueryPayload) {
  const queryPayload = createInitialWorkflowQuery(payload)

  const query = useQuery({
    queryKey: ['workflow-instances', queryPayload],
    queryFn: () => fetchWorkflowInstances(queryPayload),
  })

  return {
    query,
    instances: query.data ?? [],
    error: query.error ? toWorkflowQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}

export function useWorkflowInstance(publicId?: string | null) {
  const instanceQuery = useQuery({
    queryKey: ['workflow-instance', publicId],
    queryFn: () => fetchWorkflowInstance(publicId!),
    enabled: Boolean(publicId),
  })

  const historyQuery = useQuery({
    queryKey: ['workflow-instance-history', publicId],
    queryFn: () => fetchWorkflowInstanceHistory(publicId!),
    enabled: Boolean(publicId),
  })

  const error = instanceQuery.error ?? historyQuery.error

  return {
    instanceQuery,
    historyQuery,
    instance: instanceQuery.data ?? null,
    history: historyQuery.data ?? null,
    error: error ? toWorkflowQueryError(error) : null,
    refresh: async () => {
      await Promise.all([instanceQuery.refetch(), historyQuery.refetch()])
    },
  }
}
