import { describe, expect, it } from 'vitest'
import { buildRuntimeSearchResults } from '@/features/search/core/universal-finder'
import { resolveActivityRoute } from '@/features/activity/core/activity-actions'
import type { ActivityEntry } from '@/api/types/activity'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

describe('search integration helpers', () => {
  it('includes activity center in runtime search results', () => {
    const runtime = {
      navigationMenus: [],
      personalizationRuntime: { favorites: [], recent_items: [], shortcuts: [] },
    } as unknown as HydratedRuntimeBundle
    const results = buildRuntimeSearchResults(runtime)
    expect(results.some((result) => result.route === '/activity')).toBe(true)
  })

  it('routes activity entries to entity history pages', () => {
    const route = resolveActivityRoute({
      public_id: 'evt-1',
      action: 'updated',
      entity: { type: 'document', public_id: 'doc-1' },
      source: 'backend',
    } as ActivityEntry)
    expect(route).toBe('/documents/doc-1')
  })
})

describe('dashboard widget registration', () => {
  it('registers activity dashboard widget aliases', async () => {
    const { normalizeWidgetType } = await import('@/features/dashboards/core/dashboard-widgets')
    expect(normalizeWidgetType('activity_feed')).toBe('activity')
    expect(normalizeWidgetType('audit_summary')).toBe('audit_summary')
    expect(normalizeWidgetType('system_history')).toBe('system_history')
  })
})

describe('renderer registration', () => {
  it('registers activity_feed component binding', async () => {
    const { registerDefaultComponents } = await import('@/features/renderer/register-default-components')
    const { has } = await import('@/features/renderer/core/ComponentRegistry')
    registerDefaultComponents()
    expect(has('activity_feed')).toBe(true)
  })
})
