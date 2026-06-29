import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { addHumanTaskComment, fetchHumanTaskComments } from '@/api/endpoints/workflows'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useWorkflowComments(taskPublicId?: string | null) {
  const queryClient = useQueryClient()

  const query = useQuery({
    queryKey: ['human-task-comments', taskPublicId],
    queryFn: () => fetchHumanTaskComments(taskPublicId!),
    enabled: Boolean(taskPublicId),
  })

  const addMutation = useMutation({
    mutationFn: (body: string) => addHumanTaskComment(taskPublicId!, body),
    onSuccess: async () => {
      await Promise.all([
        queryClient.invalidateQueries({ queryKey: ['human-task-comments', taskPublicId] }),
        queryClient.invalidateQueries({ queryKey: ['human-task', taskPublicId] }),
      ])
    },
  })

  return {
    query,
    comments: query.data ?? [],
    error: query.error ? toWorkflowQueryError(query.error) : null,
    addComment: addMutation.mutateAsync,
    isAdding: addMutation.isPending,
    addError: addMutation.error ? toWorkflowQueryError(addMutation.error) : null,
    refresh: () => query.refetch(),
  }
}
