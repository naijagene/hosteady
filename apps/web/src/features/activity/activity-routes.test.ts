import { describe, expect, it } from 'vitest'
import { activityRoutes } from '@/features/activity/core/activity-navigation'

const routes = ['/activity', '/activity/audit', '/activity/document/doc-1']

describe('activity routes', () => {
  routes.forEach((route) => {
    it(`documents route ${route}`, () => {
      expect(route.startsWith('/activity')).toBe(true)
    })
  })

  it('builds entity history route', () => {
    expect(activityRoutes.entity('workflow', 'wf-1')).toBe('/activity/workflow/wf-1')
  })
})
