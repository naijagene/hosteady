import { beforeEach, describe, expect, it, vi } from 'vitest'
import type { SearchResult } from '@/api/types/search'
import { createInitialSearchQuery, mergeSearchQueryPayload, normalizeSearchQuery, shouldSearch } from './search-query'
import { scoreSearchResult, rankSearchResults, getTypePriority } from './search-ranking'
import { applySearchFilters, filterSearchResultsByTypes, createSearchTypeFilter } from './search-filters'
import { filterResultsByPermission, canSearch, canViewSearchResult } from './search-permissions'
import { resolveSearchAction, resolveSearchResultRoute } from './search-actions'
import { defaultCommands, filterCommands, commandToSearchResult } from './command-registry'
import { executeCommand } from './command-actions'
import { formatSearchSource, getSearchResultLabel } from './search-normalizer'
import { resolveSearchIcon } from './search-icons'
import { toSearchQueryError } from './search-errors'
import { buildRuntimeSearchResults, groupSearchResults, runUniversalFinder } from './universal-finder'
import { readRecentSearches, writeRecentSearch } from './recent-searches-storage'
import { ApiError } from '@/api/errors'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as searchApi from '@/api/endpoints/search'

vi.mock('@/api/endpoints/search', () => ({
  searchTenant: vi.fn(),
}))

const baseResult = (overrides: Partial<SearchResult> = {}): SearchResult => ({
  id: '1',
  title: 'Documents',
  description: 'Manager',
  type: 'document',
  icon: 'document',
  route: '/documents',
  source: 'local',
  ...overrides,
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: {
    active_applications: [{ public_id: 'app-1', name: 'Platform App', route: '/app/platform/home' }],
  } as unknown as HydratedRuntimeBundle['workspaceRuntime'],
  themeRuntime: null,
  personalizationRuntime: {
    preferences: [],
    favorites: [{ label: 'My Dashboard', route: '/dashboards/platform/home' }],
    recent_items: [{ label: 'Recent Doc', route: '/documents/doc-1' }],
    shortcuts: [{ label: 'Quick Reports', route: '/reports/platform/summary' }],
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
  navigationMenus: [
    {
      menu_key: 'main',
      label: 'Main',
      groups: [
        {
          group_key: 'core',
          label: 'Core',
          items: [
            {
              item_key: 'home',
              label: 'Home',
              route: { module_key: 'platform', page_key: 'home' },
            },
          ],
        },
      ],
      metadata: {},
    },
  ],
  permissions: ['documents.read', 'reports.read', 'dashboards.read', 'workflow.runtime.read', 'notifications.read'],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 0,
  warnings: [],
  source: 'heos_runtime',
}

describe('search query helpers', () => {
  it('creates initial query payload', () => {
    const query = createInitialSearchQuery()
    expect(query.limit).toBe(20)
    expect(query.metadata?.context).toBe('command_palette')
  })

  it('merges query payload patches', () => {
    const merged = mergeSearchQueryPayload(createInitialSearchQuery(), { query: 'docs' })
    expect(merged.query).toBe('docs')
  })

  it('normalizes query strings', () => {
    expect(normalizeSearchQuery('  Docs ')).toBe('docs')
  })

  it('detects searchable queries', () => {
    expect(shouldSearch('a')).toBe(true)
    expect(shouldSearch('   ')).toBe(false)
  })
})

describe('search ranking', () => {
  it('scores exact title matches highest', () => {
    const exact = scoreSearchResult(baseResult({ title: 'Documents' }), 'documents')
    const partial = scoreSearchResult(baseResult({ title: 'All Documents' }), 'documents')
    expect(exact).toBeGreaterThan(partial)
  })

  it('scores starts-with above contains', () => {
    const starts = scoreSearchResult(baseResult({ title: 'Documents hub' }), 'doc')
    const contains = scoreSearchResult(baseResult({ title: 'My Documents' }), 'doc')
    expect(starts).toBeGreaterThan(contains)
  })

  it('boosts command and personalization sources', () => {
    const command = scoreSearchResult(baseResult({ source: 'command', type: 'command' }), 'doc')
    const local = scoreSearchResult(baseResult({ source: 'local' }), 'doc')
    expect(command).toBeGreaterThan(local)
  })

  it('ranks results in descending score order', () => {
    const ranked = rankSearchResults(
      [baseResult({ title: 'zzz' }), baseResult({ title: 'Documents', id: '2' })],
      'documents',
    )
    expect(ranked[0].title).toBe('Documents')
  })

  it('returns type priority values', () => {
    expect(getTypePriority('command')).toBeGreaterThan(getTypePriority('custom'))
  })
})

describe('search filters', () => {
  it('applies filters to payload', () => {
    const payload = applySearchFilters(createInitialSearchQuery(), [createSearchTypeFilter('document')])
    expect(payload.filters).toHaveLength(1)
  })

  it('filters results by type', () => {
    const filtered = filterSearchResultsByTypes(
      [baseResult({ type: 'document' }), baseResult({ id: '2', type: 'report' })],
      ['document'],
    )
    expect(filtered).toHaveLength(1)
  })
})

describe('search permissions', () => {
  it('allows search when permissions empty', () => {
    expect(canSearch([])).toBe(true)
  })

  it('requires search.read when permissions present', () => {
    expect(canSearch(['documents.read'])).toBe(false)
    expect(canSearch(['search.read'])).toBe(true)
  })

  it('filters results by permission metadata', () => {
    const filtered = filterResultsByPermission(
      [
        baseResult({ permission: 'documents.read' }),
        baseResult({ id: '2', permission: 'admin.only' }),
      ],
      ['documents.read'],
    )
    expect(filtered).toHaveLength(1)
  })

  it('allows results without permission metadata', () => {
    expect(canViewSearchResult(['x'], null)).toBe(true)
  })
})

describe('search actions', () => {
  it('resolves page routes from metadata', () => {
    const route = resolveSearchResultRoute(
      baseResult({
        type: 'page',
        route: null,
        metadata: { module_key: 'platform', page_key: 'home' },
      }),
    )
    expect(route).toBe('/app/platform/home')
  })

  it('resolves document routes', () => {
    const route = resolveSearchResultRoute(
      baseResult({ type: 'document', route: null, metadata: { document_public_id: 'doc-1' } }),
    )
    expect(route).toBe('/documents/doc-1')
  })

  it('resolves navigate action from route', () => {
    const action = resolveSearchAction(baseResult())
    expect(action.action_type).toBe('navigate')
    expect(action.route).toBe('/documents')
  })

  it('resolves execute_command for command results', () => {
    const action = resolveSearchAction(
      baseResult({ type: 'command', route: null, metadata: { command_key: 'theme-dark' } }),
    )
    expect(action.action_type).toBe('execute_command')
    expect(action.command_key).toBe('theme-dark')
  })
})

describe('command registry', () => {
  it('includes navigation commands', () => {
    expect(defaultCommands.some((command) => command.command_key === 'go-documents')).toBe(true)
  })

  it('includes theme commands', () => {
    expect(defaultCommands.filter((command) => command.category === 'Theme')).toHaveLength(3)
  })

  it('filters commands by query', () => {
    const filtered = filterCommands(defaultCommands, 'dark', [])
    expect(filtered.some((command) => command.command_key === 'theme-dark')).toBe(true)
  })

  it('filters commands by permission when permissions provided', () => {
    const filtered = filterCommands(defaultCommands, '', ['documents.read'])
    expect(filtered.some((command) => command.command_key === 'go-documents')).toBe(true)
    expect(filtered.some((command) => command.command_key === 'go-settings')).toBe(false)
  })

  it('maps commands to search results', () => {
    const result = commandToSearchResult(defaultCommands[0])
    expect(result.type).toBe('command')
    expect(result.source).toBe('command')
  })
})

describe('command actions', () => {
  it('applies light theme command', async () => {
    const result = await executeCommand('theme-light')
    expect(result.success).toBe(true)
  })

  it('returns placeholder for unsupported command', async () => {
    const result = await executeCommand('unknown-command')
    expect(result.success).toBe(false)
  })
})

describe('search normalizer and icons', () => {
  it('formats source labels', () => {
    expect(formatSearchSource('backend')).toBe('Platform search')
    expect(formatSearchSource('runtime')).toBe('Runtime')
  })

  it('returns result label', () => {
    expect(getSearchResultLabel(baseResult())).toBe('Documents')
  })

  it('resolves icons by type', () => {
    expect(resolveSearchIcon('document')).toBe('document')
    expect(resolveSearchIcon('unknown-type')).toBe('search')
  })
})

describe('search errors', () => {
  it('maps ApiError to search query error', () => {
    const error = toSearchQueryError(new ApiError('Forbidden', { status: 403 }))
    expect(error.message).toBe('Forbidden')
    expect(error.status).toBe(403)
  })
})

describe('runtime search builder', () => {
  it('builds runtime results from navigation and personalization', () => {
    const results = buildRuntimeSearchResults(runtime)
    expect(results.some((result) => result.title === 'Home')).toBe(true)
    expect(results.some((result) => result.type === 'favorite')).toBe(true)
    expect(results.some((result) => result.type === 'shortcut')).toBe(true)
    expect(results.some((result) => result.type === 'recent')).toBe(true)
  })

  it('groups results by type or command source', () => {
    const groups = groupSearchResults([
      baseResult({ type: 'document' }),
      commandToSearchResult(defaultCommands[0]),
    ])
    expect(groups.document).toHaveLength(1)
    expect(groups.Commands).toHaveLength(1)
  })
})

describe('universal finder merge', () => {
  it('merges backend and runtime results', async () => {
    vi.mocked(searchApi.searchTenant).mockResolvedValue({
      query: 'doc',
      items: [baseResult({ id: 'backend-1', title: 'Backend Doc', source: 'backend' })],
      total: 1,
      source: 'backend',
    })

    const result = await runUniversalFinder({
      query: 'doc',
      runtime,
      permissions: runtime.permissions,
    })

    expect(result.items.length).toBeGreaterThan(0)
    expect(result.query).toBe('doc')
  })

  it('falls back to runtime when backend fails', async () => {
    vi.mocked(searchApi.searchTenant).mockRejectedValue(new Error('offline'))

    const result = await runUniversalFinder({
      query: 'documents',
      runtime,
      permissions: runtime.permissions,
    })

    expect(result.source).toBe('runtime')
    expect(result.items.some((item) => item.title.toLowerCase().includes('document'))).toBe(true)
  })

  it('returns suggestions when query empty', async () => {
    const result = await runUniversalFinder({
      query: '',
      runtime,
      permissions: runtime.permissions,
    })

    expect(result.items.length).toBeGreaterThan(0)
  })
})

describe('recent searches storage', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  it('reads empty recent searches initially', () => {
    expect(readRecentSearches()).toEqual([])
  })

  it('writes and deduplicates recent searches', () => {
    writeRecentSearch('documents')
    writeRecentSearch('reports')
    writeRecentSearch('documents')
    expect(readRecentSearches()[0]).toBe('documents')
    expect(readRecentSearches()).toHaveLength(2)
  })
})
