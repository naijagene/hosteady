import { describe, expect, it } from 'vitest'
import {
  bindingQueryEnabled,
  isBindingType,
  resolveComponentBinding,
} from '@/features/renderer/core/binding-resolver'
import type { UiComponent } from '@/api/types/ui'

const baseComponent: UiComponent = {
  public_id: 'cmp-1',
  component_key: 'orders-table',
  name: 'Orders',
  component_type: 'table',
  binding_type: 'table',
  binding_config: {
    module_key: 'platform',
    resource_key: 'orders',
  },
}

describe('binding-resolver', () => {
  it('resolves binding from component binding object', () => {
    const binding = resolveComponentBinding({
      ...baseComponent,
      binding: {
        binding_type: 'table',
        module_key: 'platform',
        resource_key: 'orders',
      },
    })

    expect(binding).toEqual({
      bindingType: 'table',
      moduleKey: 'platform',
      resourceKey: 'orders',
      config: expect.objectContaining({ module_key: 'platform' }),
    })
  })

  it('falls back to component module key', () => {
    const binding = resolveComponentBinding(
      {
        ...baseComponent,
        module_key: 'core',
        binding_config: { resource_key: 'users' },
      },
      'fallback',
    )

    expect(binding?.moduleKey).toBe('core')
    expect(binding?.resourceKey).toBe('users')
  })

  it('returns null when module or resource missing', () => {
    expect(
      resolveComponentBinding({
        ...baseComponent,
        binding_config: {},
        component_key: '',
      }),
    ).toBeNull()
  })

  it('checks binding type membership', () => {
    const binding = resolveComponentBinding(baseComponent)

    expect(isBindingType(binding, 'table')).toBe(true)
    expect(isBindingType(binding, 'form')).toBe(false)
    expect(isBindingType(null, 'table')).toBe(false)
  })

  it('detects query enabled config', () => {
    const binding = resolveComponentBinding({
      ...baseComponent,
      binding_config: {
        module_key: 'platform',
        resource_key: 'orders',
        query_enabled: true,
      },
    })

    expect(bindingQueryEnabled(binding)).toBe(true)
  })

  it('detects auto_query config', () => {
    const binding = resolveComponentBinding({
      ...baseComponent,
      binding_config: {
        module_key: 'platform',
        resource_key: 'orders',
        auto_query: true,
      },
    })

    expect(bindingQueryEnabled(binding)).toBe(true)
  })

  it('returns false when query not enabled', () => {
    expect(bindingQueryEnabled(resolveComponentBinding(baseComponent))).toBe(false)
    expect(bindingQueryEnabled(null)).toBe(false)
  })

  it('resolves form binding keys', () => {
    const binding = resolveComponentBinding({
      ...baseComponent,
      component_type: 'form',
      binding_type: 'form',
      binding_config: {
        module_key: 'platform',
        form_key: 'profile',
      },
    })

    expect(binding?.bindingType).toBe('form')
    expect(binding?.resourceKey).toBe('profile')
  })
})
