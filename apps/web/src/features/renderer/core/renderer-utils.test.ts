import { describe, expect, it } from 'vitest'
import {
  extractMetricValue,
  extractStaticText,
  getLayoutClassName,
  getRegionClassName,
  getRegionComponents,
  hasPermission,
  mergePermissions,
  safeMetadataClasses,
  sortComponents,
  sortRegions,
} from '@/features/renderer/core/renderer-utils'
import type { UiComponent, UiRegion } from '@/api/types/ui'

describe('renderer-utils', () => {
  it('checks permissions', () => {
    expect(hasPermission(['a.read'], 'a.read')).toBe(true)
    expect(hasPermission(['a.read'], 'b.read')).toBe(false)
    expect(hasPermission([], null)).toBe(true)
  })

  it('merges permission arrays uniquely', () => {
    expect(mergePermissions(['a'], ['b', 'a'])).toEqual(['a', 'b'])
  })

  it('sorts regions by sort_order', () => {
    const regions: UiRegion[] = [
      { region_key: 'b', region_type: 'content', label: 'B', sort_order: 2, components: [] },
      { region_key: 'a', region_type: 'content', label: 'A', sort_order: 1, components: [] },
    ]

    expect(sortRegions(regions).map((region) => region.region_key)).toEqual(['a', 'b'])
  })

  it('sorts components by sort_order', () => {
    const components: UiComponent[] = [
      {
        public_id: '2',
        component_key: 'b',
        name: 'B',
        component_type: 'custom',
        sort_order: 2,
      },
      {
        public_id: '1',
        component_key: 'a',
        name: 'A',
        component_type: 'custom',
        sort_order: 1,
      },
    ]

    expect(sortComponents(components).map((component) => component.component_key)).toEqual([
      'a',
      'b',
    ])
  })

  it('resolves region components by reference id', () => {
    const component: UiComponent = {
      public_id: 'cmp-1',
      component_key: 'hero',
      name: 'Hero',
      component_type: 'static_text',
    }
    const region: UiRegion = {
      region_key: 'main',
      region_type: 'content',
      label: 'Main',
      sort_order: 0,
      components: ['cmp-1'],
    }

    expect(getRegionComponents(region, [component])).toHaveLength(1)
  })

  it('falls back to region_key matching', () => {
    const component: UiComponent = {
      public_id: 'cmp-1',
      component_key: 'hero',
      name: 'Hero',
      component_type: 'static_text',
      region_key: 'main',
    }
    const region: UiRegion = {
      region_key: 'main',
      region_type: 'content',
      label: 'Main',
      sort_order: 0,
      components: [],
    }

    expect(getRegionComponents(region, [component])).toHaveLength(1)
  })

  it('sanitizes metadata classes', () => {
    expect(safeMetadataClasses({ className: 'p-4 text-sm' })).toBe('p-4 text-sm')
    expect(safeMetadataClasses({ className: 'bad inject' })).toBe('bad inject')
  })

  it('returns layout class names for supported types', () => {
    expect(getLayoutClassName('single_column')).toContain('single-column')
    expect(getLayoutClassName('two_column')).toContain('two-column')
    expect(getLayoutClassName('three_column')).toContain('three-column')
    expect(getLayoutClassName('sidebar')).toContain('sidebar')
    expect(getLayoutClassName('header_content')).toContain('header-content')
    expect(getLayoutClassName('dashboard_grid')).toContain('dashboard-grid')
    expect(getLayoutClassName('tabbed')).toContain('tabbed')
    expect(getLayoutClassName('wizard')).toContain('wizard')
    expect(getLayoutClassName('split_pane')).toContain('split-pane')
    expect(getLayoutClassName('unknown')).toContain('custom')
  })

  it('returns region class names', () => {
    expect(getRegionClassName('header')).toContain('header')
    expect(getRegionClassName('sidebar')).toContain('sidebar')
    expect(getRegionClassName('footer')).toContain('footer')
    expect(getRegionClassName('content')).toContain('content')
  })

  it('extracts static text from metadata', () => {
    expect(
      extractStaticText({
        public_id: '1',
        component_key: 'text',
        name: 'Title',
        component_type: 'static_text',
        metadata: { text: 'Hello world' },
      }),
    ).toBe('Hello world')
  })

  it('extracts metric values', () => {
    expect(
      extractMetricValue({
        public_id: '1',
        component_key: 'metric',
        name: 'Metric',
        component_type: 'metric',
        metadata: { value: '42' },
      }),
    ).toBe('42')
  })
})
