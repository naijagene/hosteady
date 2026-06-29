import { describe, expect, it } from 'vitest'
import {
  applyNotificationFilters,
  createNotificationCategoryFilter,
  createNotificationPriorityFilter,
  createNotificationStatusFilter,
} from '@/features/notifications/core/notification-filters'
import { createInitialNotificationQuery } from '@/features/notifications/core/notification-query'

describe('notification filters', () => {
  it('creates filter helpers', () => {
    expect(createNotificationStatusFilter('delivered').value).toBe('delivered')
    expect(createNotificationCategoryFilter('workflow').key).toBe('category')
    expect(createNotificationPriorityFilter('high').value).toBe('high')
  })

  it('applies filters to query payload', () => {
    const payload = applyNotificationFilters(createInitialNotificationQuery(), [
      createNotificationCategoryFilter('document'),
    ])
    expect(payload.filters).toHaveLength(1)
  })
})

describe('notification link matrix', () => {
  const cases = [
    [{ task_public_id: 't-1' }, '/workflows/tasks/t-1'],
    [{ approval_public_id: 'a-1' }, '/workflows/approvals/a-1'],
    [{ workflow_instance_public_id: 'i-1' }, '/workflows/instances/i-1'],
    [{ document_public_id: 'd-1' }, '/documents/d-1'],
    [{ module_key: 'platform', report_key: 'summary' }, '/reports/platform/summary'],
    [{ module_key: 'platform', dashboard_key: 'home' }, '/dashboards/platform/home'],
  ] as const

  cases.forEach(([metadata, expected]) => {
    it(`resolves ${expected}`, async () => {
      const { resolveNotificationLink } = await import('@/features/notifications/core/notification-actions')
      expect(
        resolveNotificationLink({
          public_id: 'n-1',
          title: 'Test',
          body: 'Body',
          metadata,
        }),
      ).toBe(expected)
    })
  })
})
