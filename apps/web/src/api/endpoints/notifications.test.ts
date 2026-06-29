import { beforeEach, describe, expect, it, vi } from 'vitest'
import {
  deleteNotification,
  fetchAnnouncements,
  fetchMentions,
  fetchNotification,
  fetchNotifications,
  fetchReminders,
  markAllNotificationsRead,
  markNotificationRead,
  markNotificationUnread,
} from '@/api/endpoints/notifications'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('notification API endpoints', () => {
  beforeEach(() => {
    vi.mocked(apiClient.get).mockReset()
    vi.mocked(apiClient.post).mockReset()
    vi.mocked(apiClient.patch).mockReset()
    vi.mocked(apiClient.delete).mockReset()
  })

  it('fetches notifications', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: [{ public_id: 'n-1', title: 'Alert', body: 'Message', read_at: null }],
    })
    const items = await fetchNotifications({ per_page: 10 })
    expect(items[0].public_id).toBe('n-1')
  })

  it('fetches notification detail', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { public_id: 'n-1', title: 'Alert', body: 'Message' },
    })
    const item = await fetchNotification('n-1')
    expect(item.title).toBe('Alert')
  })

  it('marks notifications read and unread', async () => {
    vi.mocked(apiClient.patch).mockResolvedValue({
      data: { public_id: 'n-1', title: 'Alert', body: 'Message', read_at: '2024-01-02' },
    })
    await markNotificationRead('n-1')
    await markNotificationUnread('n-1')
    expect(apiClient.patch).toHaveBeenCalledTimes(2)
  })

  it('deletes notifications', async () => {
    vi.mocked(apiClient.delete).mockResolvedValue({
      data: { public_id: 'n-1', title: 'Alert', body: 'Message' },
    })
    await deleteNotification('n-1')
    expect(apiClient.delete).toHaveBeenCalled()
  })

  it('falls back when dedicated feeds are unavailable', async () => {
    vi.mocked(apiClient.get)
      .mockRejectedValueOnce(new Error('missing'))
      .mockResolvedValueOnce({
        data: [{ public_id: 'a-1', title: 'Announcement', body: 'Update', metadata: { category: 'announcement' }, template_key: 'announcement.general' }],
      })

    const announcements = await fetchAnnouncements()
    expect(announcements.length).toBeGreaterThan(0)
  })

  it('loads mentions and reminders via fallback', async () => {
    vi.mocked(apiClient.get)
      .mockRejectedValueOnce(new Error('missing'))
      .mockResolvedValueOnce({
        data: [{ public_id: 'm-1', title: 'Mention', body: 'Hi', template_key: 'mention.user' }],
      })
      .mockRejectedValueOnce(new Error('missing'))
      .mockResolvedValueOnce({
        data: [{ public_id: 'r-1', title: 'Reminder', body: 'Due', template_key: 'reminder.task' }],
      })

    const mentions = await fetchMentions()
    const reminders = await fetchReminders()
    expect(mentions[0].public_id).toBe('m-1')
    expect(reminders[0].public_id).toBe('r-1')
  })

  it('throws when mark all read endpoint is unavailable', async () => {
    vi.mocked(apiClient.post).mockRejectedValue({ response: { status: 404, data: { message: 'Not found' } }, isAxiosError: true })
    await expect(markAllNotificationsRead()).rejects.toBeDefined()
  })
})
