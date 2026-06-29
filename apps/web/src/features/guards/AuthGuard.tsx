import { Navigate, useRouterState } from '@tanstack/react-router'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { useAuthStore } from '@/stores/auth-store'

export function AuthGuard({ children }: { children: React.ReactNode }) {
  const accessToken = useAuthStore((state) => state.accessToken)
  const loading = useAuthStore((state) => state.loading)
  const phase = useAuthStore((state) => state.phase)
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())
  const location = useRouterState({ select: (state) => state.location })

  if (!accessToken) {
    return (
      <Navigate
        to="/login"
        search={{ redirect: location.pathname }}
        replace
      />
    )
  }

  if (
    loading &&
    (phase === 'restoring' || phase === 'bootstrapping' || phase === 'hydrating')
  ) {
    return <LoadingOverlay label="Restoring session…" />
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" search={{ redirect: undefined }} replace />
  }

  return <>{children}</>
}
