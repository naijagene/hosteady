import { type ReactNode, useEffect } from 'react'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { useAuthStore } from '@/stores/auth-store'
import { BootstrapRecoveryPanel } from './components/BootstrapRecoveryPanel'
import { HydratedRuntimeProvider } from './HydratedRuntimeProvider'

const BOOTSTRAP_TIMEOUT_MS = 20_000

interface RuntimeLoaderProps {
  children: ReactNode
}

export function RuntimeLoader({ children }: RuntimeLoaderProps) {
  const phase = useAuthStore((state) => state.phase)
  const loading = useAuthStore((state) => state.loading)
  const switchingWorkspace = useAuthStore((state) => state.switchingWorkspace)
  const errorMessage = useAuthStore((state) => state.errorMessage)
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())
  const hasTenantScope = useAuthStore((state) => state.hasTenantScope())
  const restore = useAuthStore((state) => state.restore)

  useEffect(() => {
    if (isAuthenticated && phase === 'idle') {
      void restore()
    }
  }, [isAuthenticated, phase, restore])

  useEffect(() => {
    if (!isAuthenticated || phase === 'ready' || phase === 'error') {
      return
    }

    const timer = window.setTimeout(() => {
      const currentPhase = useAuthStore.getState().phase
      if (currentPhase !== 'ready' && currentPhase !== 'error') {
        useAuthStore.getState().setPhase('error')
        useAuthStore.getState().setErrorMessage(
          'HEOS is taking longer than expected to initialize.',
        )
        useAuthStore.getState().setLoading(false)
      }
    }, BOOTSTRAP_TIMEOUT_MS)

    return () => {
      window.clearTimeout(timer)
    }
  }, [isAuthenticated, phase])

  if (!isAuthenticated) {
    return <>{children}</>
  }

  if (phase === 'error') {
    return (
      <BootstrapRecoveryPanel
        message={errorMessage}
      />
    )
  }

  if (loading && (phase === 'restoring' || phase === 'bootstrapping' || phase === 'hydrating')) {
    return <LoadingOverlay label="Preparing HEOS workspace…" />
  }

  if (!hasTenantScope && phase !== 'ready') {
    return <LoadingOverlay label="Resolving tenant scope…" />
  }

  if (!hasTenantScope && phase === 'ready') {
    return (
      <BootstrapRecoveryPanel message="Workspace scope is unavailable for this session." />
    )
  }

  return (
    <HydratedRuntimeProvider>
      {switchingWorkspace ? (
        <LoadingOverlay label="Switching workspace…" />
      ) : (
        children
      )}
    </HydratedRuntimeProvider>
  )
}
