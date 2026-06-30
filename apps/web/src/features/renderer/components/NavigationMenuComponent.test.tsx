import { describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import { NavigationMenuComponent } from '@/features/renderer/components/NavigationMenuComponent'
import type { UiComponent } from '@/api/types/ui'

vi.mock('@tanstack/react-router', () => ({
  Link: ({
    to,
    params,
    children,
  }: {
    to: string
    params?: Record<string, string>
    children: React.ReactNode
  }) => (
    <a
      href={
        params
          ? to
              .replace('$moduleKey', params.moduleKey ?? '')
              .replace('$pageKey', params.pageKey ?? '')
              .replace('$dashboardKey', params.dashboardKey ?? '')
              .replace('$formKey', params.formKey ?? '')
              .replace('$tableKey', params.tableKey ?? '')
              .replace('$reportKey', params.reportKey ?? '')
          : to
      }
    >
      {children}
    </a>
  ),
}))

vi.mock('@/app/providers/use-navigation-context', () => ({
  useNavigationContext: () => ({
    menus: [
      {
        menu_key: 'alpha-primary',
        label: 'Alpha Primary Navigation',
        groups: [
          {
            group_key: 'default',
            label: 'Main',
            items: [
              {
                item_key: 'alpha-home',
                label: 'Alpha Preview Home',
                route: {
                  path: '/app/alpha.preview/home',
                  module_key: 'alpha.preview',
                  page_key: 'home',
                },
              },
              {
                item_key: 'alpha-dashboard',
                label: 'Alpha Dashboard',
                route: { path: '/dashboards/alpha.preview/sample', module_key: 'alpha.preview' },
              },
            ],
          },
        ],
        metadata: {},
      },
    ],
    backendMenus: [],
    fallbackMenus: [],
    usingFallbackNavigation: false,
    navigation: {},
    overrides: {},
  }),
}))

describe('NavigationMenuComponent', () => {
  it('renders clickable alpha metadata navigation links', () => {
    const component = {
      component_key: 'alpha-nav',
      name: 'Alpha Navigation',
      component_type: 'navigation_menu',
      binding_type: 'custom',
      binding_config: {},
    } as UiComponent

    render(<NavigationMenuComponent component={component} />)

    expect(screen.getByRole('link', { name: 'Alpha Preview Home' })).toHaveAttribute(
      'href',
      '/app/alpha.preview/home',
    )
    expect(screen.getByRole('link', { name: 'Alpha Dashboard' })).toHaveAttribute(
      'href',
      '/dashboards/alpha.preview/sample',
    )
  })
})
