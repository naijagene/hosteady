import { describe, expect, it } from 'vitest'
import {
  inferNotificationCategory,
  normalizeAnnouncement,
  normalizeMention,
  normalizeNotification,
  normalizeNotificationBindingContext,
  normalizeReminder,
} from '@/api/types/notifications'
import {
  buildNotificationTabCounts,
  createInitialNotificationQuery,
  queryNotificationsLocally,
  resolveTabCategory,
} from '@/features/notifications/core/notification-query'
import {
  getNotificationActionLabel,
  resolveNotificationLink,
} from '@/features/notifications/core/notification-actions'
import {
  canDeleteNotifications,
  canReadAnnouncements,
  canReadNotifications,
} from '@/features/notifications/core/notification-permissions'
import {
  getNotificationPriorityLabel,
  getNotificationPriorityTone,
} from '@/features/notifications/core/notification-priority'
import {
  formatNotificationDate,
  getNotificationDisplayTitle,
  isNotificationUnread,
} from '@/features/notifications/core/notification-normalizer'
import { resolveNotificationIcon } from '@/features/notifications/core/notification-icons'
import { toNotificationQueryError } from '@/features/notifications/core/notification-errors'
import { ApiError } from '@/api/errors'
import type { Notification } from '@/api/types/notifications'

const notification: Notification = {
  public_id: 'n-1',
  title: 'Task assigned',
  body: 'Review the request',
  status: 'delivered',
  priority: 'high',
  read_at: null,
  created_at: '2024-01-01T00:00:00.000Z',
  metadata: { task_public_id: 'task-1', event_type: 'task.assigned' },
}

describe('notification type normalization', () => {
  it('normalizes snake_case notification payloads', () => {
    const normalized = normalizeNotification({
      public_id: 'n-1',
      title: 'Hello',
      body: 'World',
      priority: 'normal',
      read_at: null,
      created_at: '2024-01-01',
    })
    expect(normalized.public_id).toBe('n-1')
    expect(normalized.category).toBe('system')
  })

  it('infers workflow category from metadata', () => {
    const normalized = normalizeNotification({
      publicId: 'n-2',
      title: 'Approval',
      body: 'Pending',
      metadata: { approval_public_id: 'ap-1' },
    })
    expect(normalized.category).toBe('workflow')
  })

  it('normalizes binding context camelCase', () => {
    const binding = normalizeNotificationBindingContext({ mode: 'compact', showCounts: true, perPage: 10 })
    expect(binding.mode).toBe('compact')
    expect(binding.show_counts).toBe(true)
    expect(binding.per_page).toBe(10)
  })
})

describe('notification query core', () => {
  it('creates initial query payload', () => {
    expect(createInitialNotificationQuery().page).toBe(1)
  })

  it('filters unread notifications locally', () => {
    const result = queryNotificationsLocally(
      [
        notification,
        { ...notification, public_id: 'n-2', read_at: '2024-01-02' },
      ],
      { unread_only: true, page: 1, per_page: 25 },
    )
    expect(result.items).toHaveLength(1)
  })

  it('builds tab counts', () => {
    const counts = buildNotificationTabCounts([
      notification,
      { ...notification, public_id: 'n-2', category: 'announcement', read_at: '2024-01-02' },
    ])
    expect(counts.unread).toBe(1)
    expect(counts.announcements).toBe(1)
  })

  it('resolves tab categories', () => {
    expect(resolveTabCategory('workflow')).toBe('workflow')
    expect(resolveTabCategory('all')).toBeUndefined()
  })
})

describe('notification actions and links', () => {
  it('resolves workflow task links', () => {
    expect(resolveNotificationLink(notification)).toBe('/workflows/tasks/task-1')
  })

  it('resolves document links', () => {
    expect(
      resolveNotificationLink({
        ...notification,
        metadata: { document_public_id: 'doc-1' },
      }),
    ).toBe('/documents/doc-1')
  })

  it('maps action labels for workflow events', () => {
    expect(getNotificationActionLabel(notification)).toBe('Open task')
  })
})

describe('notification permissions and display helpers', () => {
  it('allows read with empty permissions', () => {
    expect(canReadNotifications([])).toBe(true)
    expect(canReadAnnouncements([])).toBe(true)
  })

  it('requires explicit delete permission when permissions provided', () => {
    expect(canDeleteNotifications(['notifications.read'])).toBe(false)
    expect(canDeleteNotifications(['notifications.delete'])).toBe(true)
  })

  it('formats display helpers', () => {
    expect(getNotificationDisplayTitle(notification)).toBe('Task assigned')
    expect(isNotificationUnread(notification)).toBe(true)
    expect(formatNotificationDate(null)).toBe('—')
  })

  it('maps priority tone and icons', () => {
    expect(getNotificationPriorityTone('urgent')).toBe('danger')
    expect(getNotificationPriorityLabel('high')).toBe('high')
    expect(resolveNotificationIcon('workflow')).toBe('workflow')
  })

  it('maps query errors', () => {
    const error = toNotificationQueryError(new ApiError('Failed', { status: 422 }))
    expect(error.message).toBe('Failed')
  })
})

describe('collaboration item normalizers', () => {
  it('normalizes announcement, mention, and reminder', () => {
    expect(normalizeAnnouncement({ public_id: 'a-1', title: 'A', body: 'B', metadata: { announcement_type: 'general' } }).announcement_type).toBe('general')
    expect(normalizeMention({ public_id: 'm-1', title: 'M', body: 'B', metadata: { mentioned_by: 'Alice' } }).mentioned_by).toBe('Alice')
    expect(normalizeReminder({ public_id: 'r-1', title: 'R', body: 'B', metadata: { due_at: '2024-12-01' } }).due_at).toBe('2024-12-01')
  })

  it('infers categories from template keys', () => {
    expect(
      inferNotificationCategory({
        ...notification,
        template_key: 'announcement.general',
        metadata: {},
      }),
    ).toBe('announcement')
  })
})
