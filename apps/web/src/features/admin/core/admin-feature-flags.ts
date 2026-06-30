import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { AdminFeatureFlag } from '@/api/types/admin'
import { normalizeAdminFeatureFlags } from '@/api/types/admin'

export function buildFeatureFlags(runtime: HydratedRuntimeBundle | null | undefined): AdminFeatureFlag[] {
  const flags = runtime?.workspaceRuntime?.feature_flags
  if (!flags || Object.keys(flags).length === 0) {
    return [{ key: 'feature_flags', enabled: false, description: 'Feature flags unavailable in current runtime.' }]
  }
  return normalizeAdminFeatureFlags(flags)
}
