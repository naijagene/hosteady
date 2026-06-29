import { useQuery } from '@tanstack/react-query'
import { fetchWorkflowDefinition } from '@/api/endpoints/workflows'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useWorkflowDetail(publicId?: string | null) {
  const query = useQuery({
    queryKey: ['workflow-definition', publicId],
    queryFn: () => fetchWorkflowDefinition(publicId!),
    enabled: Boolean(publicId),
  })

  return {
    query,
    definition: query.data ?? null,
    error: query.error ? toWorkflowQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
