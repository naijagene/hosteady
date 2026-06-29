import { describe, expect, it } from 'vitest'
import {
  buildMetadataPagePath,
  resolveNavigationItemHref,
  resolveNavigationItemRoute,
} from '@/features/renderer/navigation-route'
import type { NavigationItemResponse } from '@/api/types/runtime'

describe('navigation-route', () => {
  it('builds metadata page paths', () => {
    expect(buildMetadataPagePath('platform', 'home')).toBe('/app/platform/home')
    expect(buildMetadataPagePath('a b', 'c/d')).toBe('/app/a%20b/c%2Fd')
  })

  it('resolves route from item.route metadata', () => {
    const item: NavigationItemResponse = {
      item_key: 'home',
      label: 'Home',
      route: { module_key: 'platform', page_key: 'home' },
    }

    expect(resolveNavigationItemRoute(item)).toEqual({
      to: '/app/$moduleKey/$pageKey',
      params: { moduleKey: 'platform', pageKey: 'home' },
    })
  })

  it('resolves route from item metadata fallback', () => {
    const item: NavigationItemResponse = {
      item_key: 'dashboard',
      label: 'Dashboard',
      metadata: { moduleKey: 'core', pageKey: 'overview' },
    }

    expect(resolveNavigationItemHref(item)).toBe('/app/core/overview')
  })

  it('supports direct app paths', () => {
    const item: NavigationItemResponse = {
      item_key: 'settings',
      label: 'Settings',
      route: { path: '/app/core/settings' },
    }

    expect(resolveNavigationItemRoute(item)).toEqual({ to: '/app/core/settings' })
  })

  it('returns null when route metadata missing', () => {
    expect(
      resolveNavigationItemHref({
        item_key: 'noop',
        label: 'No route',
      }),
    ).toBeNull()
  })
})
