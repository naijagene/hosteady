import { describe, expect, it } from 'vitest'
import {
  DEFAULT_NAVIGATION_GROUP_KEY,
  DEFAULT_NAVIGATION_GROUP_LABEL,
  collectNavigationGroups,
  normalizeNavigationGroup,
  normalizeNavigationMenus,
} from '@/features/runtime/core/normalize-navigation'

describe('normalizeNavigationMenus', () => {
  it('wraps flat workspace navigation items in a default group', () => {
    const menus = normalizeNavigationMenus([
      { module_key: 'demo', label: 'Demo Home', route_name: 'heos.demo.home', path: '/demo' },
      { item_key: 'demo-settings', label: 'Demo Settings' },
    ])

    expect(menus).toHaveLength(1)
    expect(menus[0]?.menu_key).toBe('main')
    expect(menus[0]?.groups[0]?.group_key).toBe(DEFAULT_NAVIGATION_GROUP_KEY)
    expect(menus[0]?.groups[0]?.label).toBe(DEFAULT_NAVIGATION_GROUP_LABEL)
    expect(menus[0]?.groups[0]?.items).toHaveLength(2)
  })

  it('fills missing group metadata on application menus', () => {
    const menus = normalizeNavigationMenus([
      {
        menu_key: 'alpha-preview',
        label: 'Alpha Primary Navigation',
        groups: [{ label: 'Alpha Primary Navigation', items: [{ item_key: 'alpha-home', label: 'Alpha Preview Home' }] }],
        metadata: {},
      },
    ] as unknown[])

    expect(menus[0]?.groups[0]?.group_key).toBe(DEFAULT_NAVIGATION_GROUP_KEY)
    expect(menus[0]?.groups[0]?.label).toBe('Alpha Primary Navigation')
    expect(menus[0]?.groups[0]?.items[0]?.label).toBe('Alpha Preview Home')
  })

  it('drops null or invalid group entries', () => {
    const menus = normalizeNavigationMenus([
      {
        menu_key: 'main',
        label: 'Main',
        groups: [
          null,
          { group_key: 'core', label: 'Core', items: [{ item_key: 'home', label: 'Home' }] },
        ],
        metadata: {},
      },
    ])

    expect(menus[0]?.groups).toHaveLength(1)
    expect(menus[0]?.groups[0]?.group_key).toBe('core')
  })

  it('wraps a bare navigation item as a default group', () => {
    const group = normalizeNavigationGroup({ item_key: 'home', label: 'Home' })

    expect(group?.group_key).toBe(DEFAULT_NAVIGATION_GROUP_KEY)
    expect(group?.label).toBe(DEFAULT_NAVIGATION_GROUP_LABEL)
    expect(group?.items[0]?.item_key).toBe('home')
  })

  it('normalizes alpha application navigation payload', () => {
    const menus = normalizeNavigationMenus([
      {
        menu_key: 'alpha-primary',
        label: 'Alpha Primary Navigation',
        groups: [
          {
            group_key: 'default',
            label: 'Alpha Primary Navigation',
            items: [
              {
                item_key: 'alpha-home',
                label: 'Alpha Preview Home',
                route: { module_key: 'alpha.preview', page_key: 'home', path: '/app/alpha.preview/home' },
              },
              {
                item_key: 'alpha-documents',
                label: 'Documents',
                route: { path: '/documents' },
              },
            ],
          },
        ],
        metadata: { source: 'navigation_designer' },
      },
    ])

    expect(menus[0]?.groups[0]?.items).toHaveLength(2)
    expect(menus[0]?.groups[0]?.items[0]?.route).toMatchObject({
      module_key: 'alpha.preview',
      page_key: 'home',
    })
  })
})

describe('collectNavigationGroups', () => {
  it('does not crash when group metadata is missing', () => {
    const groups = collectNavigationGroups([
      {
        menu_key: 'main',
        label: 'Main',
        groups: [{ label: 'Main', items: [{ item_key: 'home', label: 'Home' }] }],
        metadata: {},
      },
    ] as unknown as Parameters<typeof collectNavigationGroups>[0])

    expect(groups).toHaveLength(1)
    expect(groups[0]?.group_key).toBe(DEFAULT_NAVIGATION_GROUP_KEY)
    expect(groups[0]?.label).toBe('Main')
  })
})
