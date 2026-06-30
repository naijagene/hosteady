import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { BootstrapRecoveryPanel } from '@/features/runtime/components/BootstrapRecoveryPanel'
import { useAuthStore } from '@/stores/auth-store'

export function WorkspaceGuard({ children }: { children: React.ReactNode }) {
  const hasTenantScope = useAuthStore((state) => state.hasTenantScope())
  const phase = useAuthStore((state) => state.phase)
  const workspace = useAuthStore((state) => state.workspace)
  const errorMessage = useAuthStore((state) => state.errorMessage)

  if (phase === 'error') {
    return (
      <BootstrapRecoveryPanel message={errorMessage} />
    )
  }

  if (phase === 'bootstrapping' || phase === 'hydrating' || phase === 'restoring') {
    return <LoadingOverlay label="Loading workspace…" />
  }

  if (!hasTenantScope || !workspace?.public_id) {
    if (phase === 'idle') {
      return <LoadingOverlay label="Initializing session…" />
    }

    return (
      <BootstrapRecoveryPanel message="Workspace scope is unavailable for this session." />
    )
  }

  return <>{children}</>
}
