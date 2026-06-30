import { describe, expect, it } from 'vitest'
import { normalizeWidgetType } from '@/features/dashboards/core/dashboard-widgets'
import { buildRuntimeSearchResults } from '@/features/search/core/universal-finder'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

describe('admin dashboard integration', () => {
  it('registers admin widget aliases', () => {
    expect(normalizeWidgetType('platform_status')).toBe('platform_status')
    expect(normalizeWidgetType('runtime_status')).toBe('runtime_status')
    expect(normalizeWidgetType('feature_summary')).toBe('feature_summary')
    expect(normalizeWidgetType('organization_summary')).toBe('organization_summary')
  })
})

describe('admin search integration', () => {
  it('includes admin route in local search', () => {
    const runtime = {
      navigationMenus: [],
      personalizationRuntime: { favorites: [], recent_items: [], shortcuts: [] },
    } as unknown as HydratedRuntimeBundle
    const results = buildRuntimeSearchResults(runtime)
    expect(results.some((result) => result.route === '/admin')).toBe(true)
  })
})

describe('admin renderer registration', () => {
  it('registers platform_overview component binding', async () => {
    const { clearRegistryForTests, has } = await import('@/features/renderer/core/ComponentRegistry')
    clearRegistryForTests()
    const { registerDefaultComponents } = await import('@/features/renderer/register-default-components')
    registerDefaultComponents()
    expect(has('platform_overview')).toBe(true)
  }, 15000)

  it('registers admin dashboard widgets in widget registry', async () => {
    const { hasWidgetType } = await import('@/features/dashboards/widgets/widget-registry')
    expect(hasWidgetType('platform_status')).toBe(true)
    expect(hasWidgetType('runtime_status')).toBe(true)
    expect(hasWidgetType('feature_summary')).toBe(true)
    expect(hasWidgetType('organization_summary')).toBe(true)
  })
})
