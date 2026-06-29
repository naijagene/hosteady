import { useQuery } from '@tanstack/react-query'
import { fetchHumanTask, fetchHumanTasks } from '@/api/endpoints/workflows'
import type { WorkflowQueryPayload } from '@/api/types/workflows'
import { createInitialWorkflowQuery } from '../core/workflow-query'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useHumanTasks(payload?: WorkflowQueryPayload) {
  const queryPayload = createInitialWorkflowQuery(payload)

  const query = useQuery({
    queryKey: ['human-tasks', queryPayload],
    queryFn: () => fetchHumanTasks(queryPayload),
  })

  return {
    query,
    tasks: query.data ?? [],
    error: query.error ? toWorkflowQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}

export function useHumanTask(publicId?: string | null) {
  const query = useQuery({
    queryKey: ['human-task', publicId],
    queryFn: () => fetchHumanTask(publicId!),
    enabled: Boolean(publicId),
  })

  return {
    query,
    task: query.data ?? null,
    error: query.error ? toWorkflowQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
