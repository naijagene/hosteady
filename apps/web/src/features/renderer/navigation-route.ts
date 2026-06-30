import type { NavigationItemResponse } from '@/api/types/runtime'
import { asRecord, asString } from '@/api/types/metadata-common'

export interface NavigationRouteTarget {
  to: string
  params?: Record<string, string>
}

const PARAMETERIZED_ROUTE_PATTERNS = [
  {
    prefix: '/app/',
    to: '/app/$moduleKey/$pageKey',
    paramKeys: ['moduleKey', 'pageKey'] as const,
  },
  {
    prefix: '/dashboards/',
    to: '/dashboards/$moduleKey/$dashboardKey',
    paramKeys: ['moduleKey', 'dashboardKey'] as const,
  },
  {
    prefix: '/forms/',
    to: '/forms/$moduleKey/$formKey',
    paramKeys: ['moduleKey', 'formKey'] as const,
  },
  {
    prefix: '/tables/',
    to: '/tables/$moduleKey/$tableKey',
    paramKeys: ['moduleKey', 'tableKey'] as const,
  },
  {
    prefix: '/reports/',
    to: '/reports/$moduleKey/$reportKey',
    paramKeys: ['moduleKey', 'reportKey'] as const,
  },
] as const

const ALPHA_NAVIGATION_ITEM_ROUTES: Record<string, string> = {
  'alpha-home': '/app/alpha.preview/home',
  'alpha-dashboard': '/dashboards/alpha.preview/sample',
}

function readRouteRecord(route: NavigationItemResponse['route']): Record<string, unknown> {
  if (typeof route === 'string' && route.trim() !== '') {
    return { path: route }
  }

  return asRecord(route)
}

export function parseParameterizedNavigationPath(path: string): NavigationRouteTarget | null {
  for (const pattern of PARAMETERIZED_ROUTE_PATTERNS) {
    if (!path.startsWith(pattern.prefix)) {
      continue
    }

    const segments = path
      .slice(pattern.prefix.length)
      .split('/')
      .filter(Boolean)

    if (segments.length < pattern.paramKeys.length) {
      continue
    }

    const params: Record<string, string> = {}
    pattern.paramKeys.forEach((key, index) => {
      params[key] = decodeURIComponent(segments[index] ?? '')
    })

    return {
      to: pattern.to,
      params,
    }
  }

  return null
}

export function buildMetadataPagePath(moduleKey: string, pageKey: string): string {
  return `/app/${encodeURIComponent(moduleKey)}/${encodeURIComponent(pageKey)}`
}

export function resolveNavigationItemRoute(
  item: NavigationItemResponse,
): NavigationRouteTarget | null {
  const route = readRouteRecord(item.route)
  const metadata = asRecord(item.metadata)

  const path = asString(
    route.path ??
      route.href ??
      route.route_path ??
      route.routePath ??
      metadata.path ??
      metadata.href,
  )

  if (path.startsWith('/')) {
    const parameterized = parseParameterizedNavigationPath(path)
    if (parameterized) {
      return parameterized
    }

    return { to: path }
  }

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

  const dashboardKey = asString(
    route.dashboard_key ??
      route.dashboardKey ??
      metadata.dashboard_key ??
      metadata.dashboardKey,
  )
  if (moduleKey && dashboardKey) {
    return {
      to: '/dashboards/$moduleKey/$dashboardKey',
      params: { moduleKey, dashboardKey },
    }
  }

  const formKey = asString(route.form_key ?? route.formKey ?? metadata.form_key ?? metadata.formKey)
  if (moduleKey && formKey) {
    return {
      to: '/forms/$moduleKey/$formKey',
      params: { moduleKey, formKey },
    }
  }

  const tableKey = asString(
    route.table_key ?? route.tableKey ?? metadata.table_key ?? metadata.tableKey,
  )
  if (moduleKey && tableKey) {
    return {
      to: '/tables/$moduleKey/$tableKey',
      params: { moduleKey, tableKey },
    }
  }

  const reportKey = asString(
    route.report_key ?? route.reportKey ?? metadata.report_key ?? metadata.reportKey,
  )
  if (moduleKey && reportKey) {
    return {
      to: '/reports/$moduleKey/$reportKey',
      params: { moduleKey, reportKey },
    }
  }

  const fallbackPath = ALPHA_NAVIGATION_ITEM_ROUTES[item.item_key]
  if (fallbackPath) {
    return parseParameterizedNavigationPath(fallbackPath) ?? { to: fallbackPath }
  }

  return null
}

export function resolveNavigationItemHref(item: NavigationItemResponse): string | null {
  const target = resolveNavigationItemRoute(item)

  if (!target) {
    return null
  }

  if (target.params) {
    if (target.to === '/app/$moduleKey/$pageKey') {
      return buildMetadataPagePath(target.params.moduleKey, target.params.pageKey)
    }

    const pattern = PARAMETERIZED_ROUTE_PATTERNS.find((entry) => entry.to === target.to)
    if (pattern && target.params) {
      const segments = pattern.paramKeys.map((key) =>
        encodeURIComponent(target.params?.[key] ?? ''),
      )
      return `${pattern.prefix}${segments.join('/')}`
    }
  }

  return target.to
}
