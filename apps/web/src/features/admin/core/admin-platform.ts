import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { AdminPlatformInfo } from '@/api/types/admin'
import { normalizeAdminPlatformInfo } from '@/api/types/admin'
import packageJson from '../../../../package.json'

export function buildPlatformOverview(runtime: HydratedRuntimeBundle | null | undefined, counts: Record<string, number> = {}): AdminPlatformInfo {
  const metadata = runtime?.workspaceRuntime?.runtime_metadata ?? {}
  return normalizeAdminPlatformInfo(
    {
      runtime_metadata: metadata,
      runtime_status: runtime ? 'Hydrated' : 'Unavailable',
      feature_counts: counts,
    },
    {
      heos_version: 'HEOS Platform v1.0',
      backend_version: runtime?.workspaceRuntime?.runtime_version
        ? `Runtime v${runtime.workspaceRuntime.runtime_version}`
        : runtime?.tenantContext?.runtime_summary?.runtime_version
          ? `Runtime v${runtime.tenantContext.runtime_summary.runtime_version}`
          : 'Unknown',
      frontend_version: packageJson.version,
      environment: import.meta.env.MODE,
      build_number: import.meta.env.VITE_BUILD_NUMBER ?? import.meta.env.VITE_GIT_SHA ?? 'local-dev',
      runtime_status: runtime ? 'Hydrated' : 'Unavailable',
      feature_counts: counts,
    },
  )
}

export function formatPlatformInfoLabel(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}
