import type { NavigationItemResponse, NavigationMenuResponse } from '@/api/types/runtime'
import type { ApiRecord } from '@/api/types/api'
import type { SearchResult } from '@/api/types/search'
import { resolveSearchResultRoute } from './search-actions'

export function resolveNavigationItemRoute(item: NavigationItemResponse): string | null {
  const route = item.route
  if (!route) {
    return null
  }

  if (typeof route === 'string') {
    return route
  }

  const path = typeof route.path === 'string' ? route.path : null
  if (path) {
    return path
  }

  const moduleKey = typeof route.module_key === 'string' ? route.module_key : null
  const pageKey = typeof route.page_key === 'string' ? route.page_key : null
  if (moduleKey && pageKey) {
    return `/app/${moduleKey}/${pageKey}`
  }

  return null
}

export function navigationItemToSearchResult(item: NavigationItemResponse): SearchResult {
  return {
    id: `nav-${item.label}`,
    title: item.label,
    description: item.item_type ?? 'Navigation',
    type: 'navigation',
    icon: 'navigation',
    route: resolveNavigationItemRoute(item),
    source: 'runtime',
    permission: item.required_permission ?? null,
    metadata: item.metadata,
  }
}

export function flattenNavigationMenus(menus: NavigationMenuResponse[]): SearchResult[] {
  const results: SearchResult[] = []

  for (const menu of menus) {
    for (const group of menu.groups ?? []) {
      for (const item of group.items ?? []) {
        results.push(navigationItemToSearchResult(item))
      }
    }
  }

  return results
}

export function personalizationItemToSearchResult(
  item: ApiRecord,
  type: 'favorite' | 'shortcut' | 'recent',
): SearchResult {
  const label = typeof item.label === 'string' ? item.label : typeof item.title === 'string' ? item.title : 'Item'
  const route = typeof item.route === 'string' ? item.route : null
  return {
    id: `${type}-${label}`,
    title: label,
    description: type,
    type,
    icon: type,
    route,
    source: 'personalization',
    metadata: item,
  }
}

export function applicationToSearchResult(app: ApiRecord): SearchResult {
  const name = typeof app.name === 'string' ? app.name : typeof app.label === 'string' ? app.label : 'Application'
  return {
    id: `app-${typeof app.public_id === 'string' ? app.public_id : name}`,
    title: name,
    description: typeof app.description === 'string' ? app.description : 'Application',
    type: 'application',
    icon: 'application',
    route: typeof app.route === 'string' ? app.route : '/',
    source: 'runtime',
    metadata: app,
  }
}

export function ensureResultRoute(result: SearchResult): SearchResult {
  return {
    ...result,
    route: result.route ?? resolveSearchResultRoute(result),
  }
}
