import { useMutation, useQueryClient } from '@tanstack/react-query'
import {
  cancelHumanTask,
  completeHumanTask,
  openHumanTask,
} from '@/api/endpoints/workflows'
import type { TaskCompletionPayload } from '@/api/types/workflows'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useTaskActions() {
  const queryClient = useQueryClient()

  const invalidate = async (publicId?: string) => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['workflow-inbox-tasks'] }),
      queryClient.invalidateQueries({ queryKey: ['human-tasks'] }),
      queryClient.invalidateQueries({ queryKey: ['human-task', publicId] }),
      queryClient.invalidateQueries({ queryKey: ['human-task-comments', publicId] }),
      queryClient.invalidateQueries({ queryKey: ['human-task-history', publicId] }),
    ])
  }

  const openMutation = useMutation({
    mutationFn: (publicId: string) => openHumanTask(publicId),
    onSuccess: async (_data, publicId) => invalidate(publicId),
  })

  const completeMutation = useMutation({
    mutationFn: ({ publicId, payload }: { publicId: string; payload?: TaskCompletionPayload }) =>
      completeHumanTask(publicId, payload),
    onSuccess: async (_data, variables) => invalidate(variables.publicId),
  })

  const cancelMutation = useMutation({
    mutationFn: (publicId: string) => cancelHumanTask(publicId),
    onSuccess: async (_data, publicId) => invalidate(publicId),
  })

  const resolveError = (error: unknown) => (error ? toWorkflowQueryError(error) : null)

  return {
    openTask: openMutation.mutateAsync,
    completeTask: completeMutation.mutateAsync,
    cancelTask: cancelMutation.mutateAsync,
    isOpening: openMutation.isPending,
    isCompleting: completeMutation.isPending,
    isCancelling: cancelMutation.isPending,
    openError: resolveError(openMutation.error),
    completeError: resolveError(completeMutation.error),
    cancelError: resolveError(cancelMutation.error),
  }
}
