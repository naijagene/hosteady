import { Navigate, useRouterState } from '@tanstack/react-router'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { sanitizeRedirectTarget } from '@/features/auth/core/redirect-sanitize'
import { BootstrapRecoveryPanel } from '@/features/runtime/components/BootstrapRecoveryPanel'
import { useAuthStore } from '@/stores/auth-store'

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const accessToken = useAuthStore((state) => state.accessToken)
  const loading = useAuthStore((state) => state.loading)
  const phase = useAuthStore((state) => state.phase)
  const errorMessage = useAuthStore((state) => state.errorMessage)
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())
  const location = useRouterState({ select: (state) => state.location })
  const redirectTarget = sanitizeRedirectTarget(location.pathname)

  if (!accessToken || !isAuthenticated) {
    return (
      <Navigate
        to="/login"
        search={redirectTarget ? { redirect: redirectTarget } : { redirect: undefined }}
        replace
      />
    )
  }

  if (phase === 'error') {
    return (
      <BootstrapRecoveryPanel
        message={errorMessage}
      />
    )
  }

  if (
    loading &&
    (phase === 'restoring' || phase === 'bootstrapping' || phase === 'hydrating')
  ) {
    return <LoadingOverlay label="Restoring session…" />
  }

  return <>{children}</>
}
