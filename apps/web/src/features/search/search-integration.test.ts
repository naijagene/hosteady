import { describe, expect, it } from 'vitest'
import { defaultCommands } from '@/features/search/core/command-registry'
import { buildRuntimeSearchResults } from '@/features/search/core/universal-finder'
import { filterResultsByPermission } from '@/features/search/core/search-permissions'
import { rankSearchResults } from '@/features/search/core/search-ranking'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: {
    preferences: [],
    favorites: [{ label: 'Notifications', route: '/notifications' }],
    recent_items: [{ label: 'Workflow inbox', route: '/workflows' }],
    shortcuts: [],
    quick_actions: [],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: {},
    dashboard_overrides: {},
    table_overrides: {},
    notification_preferences_reference: { panel_position: 'top-right' },
    warnings: [],
    source: 'personalization_framework',
    runtime_context: {
      organization_public_id: null,
      workspace_public_id: null,
      membership_public_id: null,
      status: 'ok',
      missing_tables: [],
    },
  },
  navigationMenus: [],
  permissions: ['notifications.read', 'workflow.runtime.read'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 2,
  warnings: [],
  source: 'heos_runtime',
}

describe('personalization integration', () => {
  it('includes favorites in runtime search results', () => {
    const results = buildRuntimeSearchResults(runtime)
    expect(results.some((result) => result.type === 'favorite')).toBe(true)
  })

  it('includes recent items in runtime search results', () => {
    const results = buildRuntimeSearchResults(runtime)
    expect(results.some((result) => result.type === 'recent')).toBe(true)
  })

  it('boosts favorites during ranked search', () => {
    const results = rankSearchResults(buildRuntimeSearchResults(runtime), 'notifications')
    expect(results[0].type === 'favorite' || results[0].title.toLowerCase().includes('notification')).toBe(true)
  })

  it('includes notification route references', () => {
    const results = buildRuntimeSearchResults(runtime)
    expect(results.some((result) => result.route === '/notifications')).toBe(true)
  })
})

describe('command palette navigation coverage', () => {
  const keys = ['go-home', 'go-documents', 'go-reports', 'go-dashboards', 'go-workflows', 'go-notifications', 'go-settings']

  keys.forEach((key) => {
    it(`includes ${key} command`, () => {
      expect(defaultCommands.some((command) => command.command_key === key)).toBe(true)
    })
  })
})

describe('permission-aware search results', () => {
  it('hides documents without documents.read', () => {
    const results = filterResultsByPermission(buildRuntimeSearchResults(runtime), ['notifications.read'])
    expect(results.some((result) => result.route === '/documents' && result.permission === 'documents.read')).toBe(false)
  })
})
