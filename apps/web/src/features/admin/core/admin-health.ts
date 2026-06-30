import type { AdminPlatformHealth } from '@/api/types/admin'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

export function deriveRuntimeHealth(runtime: HydratedRuntimeBundle | null | undefined): AdminPlatformHealth {
  if (!runtime) {
    return { status: 'unavailable', summary: 'Runtime is not hydrated.', source: 'local' }
  }

  const warnings = runtime.warnings ?? []
  const missingTables = runtime.personalizationRuntime?.runtime_context?.missing_tables ?? []
  const moduleDiagnostics = runtime.workspaceRuntime?.module_diagnostics ?? {}

  if (missingTables.length > 0 || warnings.length > 0) {
    return {
      status: 'warning',
      summary: warnings[0] ?? 'Runtime loaded with warnings.',
      diagnostics: { warnings, missing_tables: missingTables, module_diagnostics: moduleDiagnostics },
      recommendations: warnings,
      source: 'runtime',
    }
  }

  return {
    status: 'healthy',
    summary: 'Runtime hydrated successfully.',
    diagnostics: { module_diagnostics: moduleDiagnostics },
    source: 'runtime',
  }
}

export function mergePlatformHealth(
  backend: AdminPlatformHealth | null | undefined,
  runtime: AdminPlatformHealth,
): AdminPlatformHealth {
  if (!backend) return runtime
  if (backend.status === 'unavailable') return runtime
  if (runtime.status === 'warning' && backend.status === 'healthy') return runtime
  return backend
}

export function getHealthLabel(status: AdminPlatformHealth['status']): string {
  switch (status) {
    case 'healthy':
      return 'Healthy'
    case 'warning':
      return 'Warning'
    case 'unavailable':
      return 'Unavailable'
    default:
      return 'Unknown'
  }
}
