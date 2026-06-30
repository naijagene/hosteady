import { useMemo } from 'react'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { useAuthStore } from '@/stores/auth-store'
import { buildAlphaHealthSnapshot } from '../core/alpha-health'

export function useAlphaHealth() {
  const runtime = useHydratedRuntime()
  const accessToken = useAuthStore((state) => state.accessToken)
  const organizationPublicId = useAuthStore((state) => state.organizationPublicId)
  const workspacePublicId = useAuthStore((state) => state.workspacePublicId)

  return useMemo(
    () =>
      buildAlphaHealthSnapshot({
        authenticated: Boolean(accessToken),
        organizationPublicId,
        workspacePublicId,
        runtime,
        runtimeEndpointStatus: runtime ? 'hydrated' : null,
      }),
    [accessToken, organizationPublicId, runtime, workspacePublicId],
  )
}
