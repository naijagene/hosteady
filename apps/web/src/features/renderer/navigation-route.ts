import type { NavigationItemResponse } from '@/api/types/runtime'
import { asRecord, asString } from '@/api/types/metadata-common'

export interface NavigationRouteTarget {
  to: string
  params?: {
    moduleKey: string
    pageKey: string
  }
}

export function buildMetadataPagePath(moduleKey: string, pageKey: string): string {
  return `/app/${encodeURIComponent(moduleKey)}/${encodeURIComponent(pageKey)}`
}

export function resolveNavigationItemRoute(
  item: NavigationItemResponse,
): NavigationRouteTarget | null {
  const route = asRecord(item.route)
  const metadata = asRecord(item.metadata)

  const moduleKey = asString(
    route.module_key ??
      route.moduleKey ??
      metadata.module_key ??
      metadata.moduleKey,
  )
  const pageKey = asString(
    route.page_key ?? route.pageKey ?? metadata.page_key ?? metadata.pageKey,
  )

  if (moduleKey && pageKey) {
    return {
      to: '/app/$moduleKey/$pageKey',
      params: { moduleKey, pageKey },
    }
  }

  const path = asString(route.path ?? route.href ?? metadata.path)

  if (path.startsWith('/app/')) {
    return { to: path }
  }

  return null
}

export function resolveNavigationItemHref(item: NavigationItemResponse): string | null {
  const target = resolveNavigationItemRoute(item)

  if (!target) {
    return null
  }

  if (target.params) {
    return buildMetadataPagePath(target.params.moduleKey, target.params.pageKey)
  }

  return target.to
}
