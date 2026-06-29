import { beforeEach, describe, expect, it, vi } from 'vitest'
import { renderHook, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { useNotifications } from '@/features/notifications/hooks/useNotifications'
import { useNotificationCenter } from '@/features/notifications/hooks/useNotificationCenter'
import { useNotificationActions } from '@/features/notifications/hooks/useNotificationActions'
import { useAnnouncements } from '@/features/notifications/hooks/useAnnouncements'
import { useMentions } from '@/features/notifications/hooks/useMentions'
import { useReminders } from '@/features/notifications/hooks/useReminders'
import * as notificationsApi from '@/api/endpoints/notifications'

function createWrapper() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return ({ children }: { children: React.ReactNode }) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
  )
}

describe('notification hooks', () => {
  beforeEach(() => {
    vi.spyOn(notificationsApi, 'fetchNotifications').mockResolvedValue([
      {
        public_id: 'n-1',
        title: 'Workflow completed',
        body: 'Done',
        category: 'workflow',
        read_at: null,
        created_at: '2024-01-01',
      },
    ])
    vi.spyOn(notificationsApi, 'fetchAnnouncements').mockResolvedValue([
      { public_id: 'a-1', title: 'Announcement', body: 'Update', category: 'announcement' },
    ])
    vi.spyOn(notificationsApi, 'fetchMentions').mockResolvedValue([
      { public_id: 'm-1', title: 'Mention', body: 'Tagged', category: 'mention' },
    ])
    vi.spyOn(notificationsApi, 'fetchReminders').mockResolvedValue([
      { public_id: 'r-1', title: 'Reminder', body: 'Due soon', category: 'reminder' },
    ])
    vi.spyOn(notificationsApi, 'markNotificationRead').mockResolvedValue({
      public_id: 'n-1',
      title: 'Workflow completed',
      body: 'Done',
      read_at: '2024-01-02',
    })
    vi.spyOn(notificationsApi, 'markNotificationUnread').mockResolvedValue({
      public_id: 'n-1',
      title: 'Workflow completed',
      body: 'Done',
      read_at: null,
    })
    vi.spyOn(notificationsApi, 'deleteNotification').mockResolvedValue({
      public_id: 'n-1',
      title: 'Workflow completed',
      body: 'Done',
    })
  })

  it('loads notifications', async () => {
    const { result } = renderHook(() => useNotifications(), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.notifications).toHaveLength(1))
  })

  it('loads notification center state', async () => {
    const { result } = renderHook(() => useNotificationCenter(), { wrapper: createWrapper() })
    await waitFor(() => expect(result.current.items.length).toBeGreaterThan(0))
    expect(result.current.counts.unread).toBe(1)
  })

  it('performs notification actions', async () => {
    const { result } = renderHook(() => useNotificationActions(), { wrapper: createWrapper() })
    await result.current.markRead('n-1')
    await result.current.markUnread('n-1')
    await result.current.deleteNotification('n-1')
    expect(notificationsApi.markNotificationRead).toHaveBeenCalled()
  })

  it('loads announcements, mentions, and reminders', async () => {
    const announcements = renderHook(() => useAnnouncements(), { wrapper: createWrapper() })
    const mentions = renderHook(() => useMentions(), { wrapper: createWrapper() })
    const reminders = renderHook(() => useReminders(), { wrapper: createWrapper() })

    await waitFor(() => expect(announcements.result.current.announcements).toHaveLength(1))
    await waitFor(() => expect(mentions.result.current.mentions).toHaveLength(1))
    await waitFor(() => expect(reminders.result.current.reminders).toHaveLength(1))
  })
})
