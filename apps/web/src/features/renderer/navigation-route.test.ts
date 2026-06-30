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

    expect(resolveNavigationItemRoute(item)).toEqual({
      to: '/app/$moduleKey/$pageKey',
      params: { moduleKey: 'core', pageKey: 'settings' },
    })
    expect(resolveNavigationItemHref(item)).toBe('/app/core/settings')
  })

  it('supports feature routes outside /app', () => {
    const item: NavigationItemResponse = {
      item_key: 'documents',
      label: 'Documents',
      route: { path: '/documents' },
    }

    expect(resolveNavigationItemRoute(item)).toEqual({ to: '/documents' })
  })

  it('resolves alpha metadata home route', () => {
    expect(
      resolveNavigationItemHref({
        item_key: 'alpha-home',
        label: 'Alpha Preview Home',
        route: { module_key: 'alpha.preview', page_key: 'home' },
      }),
    ).toBe('/app/alpha.preview/home')
  })

  it('resolves alpha dashboard route from path metadata', () => {
    expect(
      resolveNavigationItemRoute({
        item_key: 'alpha-dashboard',
        label: 'Alpha Dashboard',
        route: { path: '/dashboards/alpha.preview/sample', module_key: 'alpha.preview' },
      }),
    ).toEqual({
      to: '/dashboards/$moduleKey/$dashboardKey',
      params: { moduleKey: 'alpha.preview', dashboardKey: 'sample' },
    })
    expect(
      resolveNavigationItemHref({
        item_key: 'alpha-dashboard',
        label: 'Alpha Dashboard',
        route: { path: '/dashboards/alpha.preview/sample', module_key: 'alpha.preview' },
      }),
    ).toBe('/dashboards/alpha.preview/sample')
  })

  it('resolves string route values from backend payloads', () => {
    expect(
      resolveNavigationItemHref({
        item_key: 'alpha-home',
        label: 'Alpha Preview Home',
        route: '/app/alpha.preview/home',
      }),
    ).toBe('/app/alpha.preview/home')
  })

  it('falls back to alpha item routes when metadata is label-only', () => {
    expect(
      resolveNavigationItemHref({
        item_key: 'alpha-home',
        label: 'Alpha Preview Home',
      }),
    ).toBe('/app/alpha.preview/home')
    expect(
      resolveNavigationItemHref({
        item_key: 'alpha-dashboard',
        label: 'Alpha Dashboard',
      }),
    ).toBe('/dashboards/alpha.preview/sample')
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
