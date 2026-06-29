import { Navigate } from '@tanstack/react-router'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { useAuthStore } from '@/stores/auth-store'

export function WorkspaceGuard({ children }: { children: React.ReactNode }) {
  const hasTenantScope = useAuthStore((state) => state.hasTenantScope())
  const phase = useAuthStore((state) => state.phase)
  const workspace = useAuthStore((state) => state.workspace)

  if (phase === 'bootstrapping' || phase === 'hydrating') {
    return <LoadingOverlay label="Loading workspace…" />
  }

  if (!hasTenantScope || !workspace?.public_id) {
    return <Navigate to="/loading" replace />
  }

  return <>{children}</>
}
