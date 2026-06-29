import { describe, expect, it } from 'vitest'
import {
  normalizeUiComponent,
  normalizeUiPage,
  normalizeUiRenderPayload,
} from '@/api/types/ui'

describe('ui metadata types', () => {
  it('normalizes camelCase page payload', () => {
    const page = normalizeUiPage({
      pageKey: 'home',
      name: 'Home',
      moduleKey: 'platform',
    })

    expect(page.page_key).toBe('home')
    expect(page.module_key).toBe('platform')
  })

  it('normalizes render payload defensively', () => {
    const payload = normalizeUiRenderPayload({
      page: { page_key: 'home', name: 'Home' },
      layout: { layout_key: 'default', name: 'Default', layout_type: 'single_column' },
      regions: null,
      components: undefined,
      actions: [{ actionKey: 'save', label: 'Save' }],
      permissions: ['platform.read'],
    })

    expect(payload.regions).toEqual([])
    expect(payload.components).toEqual([])
    expect(payload.actions[0].action_key).toBe('save')
    expect(payload.permissions).toEqual(['platform.read'])
  })

  it('normalizes component binding config', () => {
    const component = normalizeUiComponent({
      publicId: 'cmp-1',
      componentKey: 'table',
      name: 'Table',
      componentType: 'table',
      bindingType: 'table',
      bindingConfig: { module_key: 'platform', resource_key: 'users' },
    })

    expect(component.component_type).toBe('table')
    expect(component.binding?.module_key).toBe('platform')
    expect(component.binding?.resource_key).toBe('users')
  })
})
