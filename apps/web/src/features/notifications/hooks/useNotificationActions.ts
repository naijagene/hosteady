import { useMutation, useQueryClient } from '@tanstack/react-query'
import {
  deleteNotification,
  markAllNotificationsRead,
  markNotificationRead,
  markNotificationUnread,
} from '@/api/endpoints/notifications'
import { toNotificationActionError } from '../core/notification-errors'

export function useNotificationActions() {
  const queryClient = useQueryClient()

  const invalidate = async (publicId?: string) => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['notifications-query'] }),
      queryClient.invalidateQueries({ queryKey: ['notification', publicId] }),
      queryClient.invalidateQueries({ queryKey: ['notification-bell-preview'] }),
    ])
  }

  const markReadMutation = useMutation({
    mutationFn: (publicId: string) => markNotificationRead(publicId),
    onSuccess: async (_data, publicId) => invalidate(publicId),
  })

  const markUnreadMutation = useMutation({
    mutationFn: (publicId: string) => markNotificationUnread(publicId),
    onSuccess: async (_data, publicId) => invalidate(publicId),
  })

  const markAllReadMutation = useMutation({
    mutationFn: () => markAllNotificationsRead(),
    onSuccess: async () => invalidate(),
  })

  const deleteMutation = useMutation({
    mutationFn: (publicId: string) => deleteNotification(publicId),
    onSuccess: async (_data, publicId) => invalidate(publicId),
  })

  const resolveError = (error: unknown) => (error ? toNotificationActionError(error) : null)

  return {
    markRead: markReadMutation.mutateAsync,
    markUnread: markUnreadMutation.mutateAsync,
    markAllRead: markAllReadMutation.mutateAsync,
    deleteNotification: deleteMutation.mutateAsync,
    isMarkingRead: markReadMutation.isPending,
    isMarkingUnread: markUnreadMutation.isPending,
    isMarkingAllRead: markAllReadMutation.isPending,
    isDeleting: deleteMutation.isPending,
    markReadError: resolveError(markReadMutation.error),
    markAllReadError: resolveError(markAllReadMutation.error),
    deleteError: resolveError(deleteMutation.error),
  }
}
