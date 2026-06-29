import { useMutation, useQueryClient } from '@tanstack/react-query'
import { approveRequest, rejectRequest } from '@/api/endpoints/workflows'
import type { ApprovalDecisionPayload } from '@/api/types/workflows'
import { toWorkflowQueryError } from '../core/workflow-errors'

export function useApprovalActions() {
  const queryClient = useQueryClient()

  const invalidate = async (publicId?: string) => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['workflow-inbox-approvals'] }),
      queryClient.invalidateQueries({ queryKey: ['approvals'] }),
      queryClient.invalidateQueries({ queryKey: ['approval', publicId] }),
    ])
  }

  const approveMutation = useMutation({
    mutationFn: ({ publicId, payload }: { publicId: string; payload?: ApprovalDecisionPayload }) =>
      approveRequest(publicId, payload),
    onSuccess: async (_data, variables) => invalidate(variables.publicId),
  })

  const rejectMutation = useMutation({
    mutationFn: ({ publicId, payload }: { publicId: string; payload?: ApprovalDecisionPayload }) =>
      rejectRequest(publicId, payload),
    onSuccess: async (_data, variables) => invalidate(variables.publicId),
  })

  const resolveError = (error: unknown) => (error ? toWorkflowQueryError(error) : null)

  return {
    approve: approveMutation.mutateAsync,
    reject: rejectMutation.mutateAsync,
    isApproving: approveMutation.isPending,
    isRejecting: rejectMutation.isPending,
    approveError: resolveError(approveMutation.error),
    rejectError: resolveError(rejectMutation.error),
  }
}
