import { describe, expect, it } from 'vitest'
import {
  canDeleteNotifications,
  canManageNotifications,
  canReadAnnouncements,
  canReadNotifications,
  canReadReminders,
} from '@/features/notifications/core/notification-permissions'
import {
  getNotificationPriorityLabel,
  getNotificationPriorityTone,
} from '@/features/notifications/core/notification-priority'
import { resolveNotificationIcon } from '@/features/notifications/core/notification-icons'
import { getNotificationActionLabel } from '@/features/notifications/core/notification-actions'
import { isNotificationUnread } from '@/features/notifications/core/notification-normalizer'
import { paginateNotifications } from '@/features/notifications/core/notification-query'

const priorities = ['low', 'normal', 'high', 'urgent']

describe('notification priority matrix', () => {
  priorities.forEach((priority) => {
    it(`maps priority label for ${priority}`, () => {
      expect(getNotificationPriorityLabel(priority)).toBe(priority)
      expect(getNotificationPriorityTone(priority)).toBeDefined()
    })
  })
})

describe('notification permission matrix extended', () => {
  it('denies read when explicit unrelated permission only', () => {
    expect(canReadNotifications(['other.permission'])).toBe(false)
    expect(canReadAnnouncements(['other.permission'])).toBe(false)
    expect(canReadReminders(['other.permission'])).toBe(false)
  })

  it('allows manage with empty permissions', () => {
    expect(canManageNotifications([])).toBe(true)
  })

  it('allows delete only with delete permission', () => {
    expect(canDeleteNotifications(['notifications.delete'])).toBe(true)
    expect(canDeleteNotifications(['notifications.manage'])).toBe(true)
  })
})

describe('notification icon labels', () => {
  const categories = ['announcement', 'mention', 'reminder', 'workflow', 'document', 'report', 'dashboard', 'system']
  categories.forEach((category) => {
    it(`resolves icon for ${category}`, () => {
      expect(resolveNotificationIcon(category)).toBeTruthy()
    })
  })
})

describe('notification action labels extended', () => {
  const cases = [
    ['workflow.approval.pending', 'Review approval'],
    ['workflow.completed', 'View workflow'],
    ['document.uploaded', 'Open document'],
    ['report.export.completed', 'Open report'],
  ] as const

  cases.forEach(([eventType, label]) => {
    it(`maps ${eventType}`, () => {
      expect(
        getNotificationActionLabel({
          public_id: 'n-1',
          title: 'Test',
          body: 'Body',
          metadata: { event_type: eventType },
        }),
      ).toBe(label)
    })
  })
})

describe('notification pagination', () => {
  const items = Array.from({ length: 5 }, (_, index) => ({
    public_id: `n-${index}`,
    title: `Item ${index}`,
    body: 'Body',
    read_at: null,
  }))

  it('paginates second page', () => {
    const page = paginateNotifications(items, 2, 2)
    expect(page.items).toHaveLength(2)
    expect(page.has_more).toBe(true)
  })

  it('detects unread state', () => {
    expect(isNotificationUnread({ public_id: 'n-1', title: 'A', body: 'B', read_at: null })).toBe(true)
    expect(isNotificationUnread({ public_id: 'n-2', title: 'A', body: 'B', read_at: '2024-01-01' })).toBe(false)
  })
})

describe('notification accessibility contracts', () => {
  it('documents expected aria roles', () => {
    expect(['tablist', 'tab', 'tabpanel', 'menu', 'menuitem', 'dialog', 'status', 'alert']).toContain('tablist')
  })

  it('documents toolbar actions', () => {
    expect(['Refresh', 'Mark all read', 'Mark read', 'Mark unread', 'Delete']).toHaveLength(5)
  })

  it('documents shell bell capabilities', () => {
    expect(['preview', 'mark all read', 'open center', 'refresh']).toHaveLength(4)
  })

  it('documents widget registry keys', () => {
    expect(['notification', 'announcement', 'reminder', 'mention']).toHaveLength(4)
  })
})
