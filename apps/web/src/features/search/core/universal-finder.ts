import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { SearchQueryResult, SearchResult, UniversalFinderContext } from '@/api/types/search'
import { searchTenant } from '@/api/endpoints/search'
import { commandToSearchResult, defaultCommands, filterCommands } from './command-registry'
import { filterResultsByPermission } from './search-permissions'
import { rankSearchResults } from './search-ranking'
import { normalizeSearchQuery, shouldSearch } from './search-query'
import {
  applicationToSearchResult,
  flattenNavigationMenus,
  personalizationItemToSearchResult,
  ensureResultRoute,
} from './search-navigation'
import { asRecord } from '@/api/types/metadata-common'

const platformRoutes: SearchResult[] = [
  {
    id: 'route-documents',
    title: 'Documents',
    description: 'Document manager',
    type: 'document',
    icon: 'document',
    route: '/documents',
    source: 'local',
    permission: 'documents.read',
  },
  {
    id: 'route-workflows',
    title: 'Workflow Inbox',
    description: 'Tasks and approvals',
    type: 'workflow',
    icon: 'workflow',
    route: '/workflows',
    source: 'local',
    permission: 'workflow.runtime.read',
  },
  {
    id: 'route-notifications',
    title: 'Notifications',
    description: 'Notification center',
    type: 'notification',
    icon: 'notification',
    route: '/notifications',
    source: 'local',
    permission: 'notifications.read',
  },
  {
    id: 'route-search',
    title: 'Search',
    description: 'Full-page search',
    type: 'page',
    icon: 'search',
    route: '/search',
    source: 'local',
  },
]

function searchLocalCollection(results: SearchResult[], query: string): SearchResult[] {
  const normalized = normalizeSearchQuery(query)
  if (!normalized) {
    return results
  }

  return results.filter((result) => {
    const haystack = [result.title, result.description ?? '', result.type].join(' ').toLowerCase()
    return haystack.includes(normalized)
  })
}

export function buildRuntimeSearchResults(runtime: HydratedRuntimeBundle | null | undefined): SearchResult[] {
  if (!runtime) {
    return []
  }

  const applications = (runtime.workspaceRuntime?.active_applications ?? []).map((app) =>
    applicationToSearchResult(asRecord(app)),
  )
  const navigation = flattenNavigationMenus(runtime.navigationMenus ?? [])
  const favorites = (runtime.personalizationRuntime?.favorites ?? []).map((item) =>
    personalizationItemToSearchResult(asRecord(item), 'favorite'),
  )
  const recentItems = (runtime.personalizationRuntime?.recent_items ?? []).map((item) =>
    personalizationItemToSearchResult(asRecord(item), 'recent'),
  )
  const shortcuts = (runtime.personalizationRuntime?.shortcuts ?? []).map((item) =>
    personalizationItemToSearchResult(asRecord(item), 'shortcut'),
  )

  return [...platformRoutes, ...applications, ...navigation, ...favorites, ...recentItems, ...shortcuts].map(
    ensureResultRoute,
  )
}

export function buildCommandSearchResults(query: string, permissions: string[]): SearchResult[] {
  return filterCommands(defaultCommands, query, permissions).map(commandToSearchResult)
}

export async function runUniversalFinder(options: {
  query: string
  runtime: HydratedRuntimeBundle | null | undefined
  permissions: string[]
  context?: UniversalFinderContext
}): Promise<SearchQueryResult> {
  const { query, runtime, permissions, context } = options
  const limit = context?.limit ?? 20
  const localResults = buildRuntimeSearchResults(runtime)
  const commandResults = context?.include_commands === false ? [] : buildCommandSearchResults(query, permissions)
  const localMatches = searchLocalCollection(localResults, query)
  let backendResults: SearchResult[] = []
  let source: SearchQueryResult['source'] = 'runtime'

  if (context?.include_backend !== false && shouldSearch(query)) {
    try {
      const backend = await searchTenant({
        query,
        limit,
        metadata: { source: 'web', context: 'universal_finder' },
      })
      backendResults = backend.items.map((item) => ({ ...item, source: 'backend' as const }))
      source = backend.items.length > 0 ? 'backend' : 'runtime'
    } catch {
      source = 'runtime'
    }
  }

  const merged = filterResultsByPermission(
    rankSearchResults([...backendResults, ...localMatches, ...commandResults], query),
    permissions,
  ).slice(0, limit)

  return {
    query,
    items: merged,
    total: merged.length,
    source,
  }
}

export function groupSearchResults(results: SearchResult[]): Record<string, SearchResult[]> {
  return results.reduce<Record<string, SearchResult[]>>((groups, result) => {
    const key = result.source === 'command' ? 'Commands' : result.type
    groups[key] = groups[key] ?? []
    groups[key].push(result)
    return groups
  }, {})
}
