import { describe, expect, it } from 'vitest'
import {
  normalizeNotification,
  normalizeNotificationBindingContext,
  normalizeNotificationPreference,
} from '@/api/types/notifications'

describe('notification API types', () => {
  it('normalizes camelCase payloads', () => {
    const notification = normalizeNotification({
      publicId: 'n-1',
      title: 'Alert',
      body: 'Message',
      readAt: null,
      createdAt: '2024-01-01',
      mergeData: { sender: 'System' },
      metadata: { event_type: 'report.generated', report_key: 'summary', module_key: 'platform' },
    })

    expect(notification.public_id).toBe('n-1')
    expect(notification.sender).toBe('System')
    expect(notification.category).toBe('report')
  })

  it('normalizes preferences and binding', () => {
    const preference = normalizeNotificationPreference({
      publicId: 'p-1',
      channel: 'in_app',
      type: 'workflow',
      enabled: true,
    })
    expect(preference.public_id).toBe('p-1')

    const binding = normalizeNotificationBindingContext({ mode: 'dropdown', deleteEnabled: true })
    expect(binding.mode).toBe('dropdown')
    expect(binding.delete_enabled).toBe(true)
  })
})
