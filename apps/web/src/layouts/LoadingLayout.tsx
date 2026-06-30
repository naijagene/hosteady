import { useEffect } from 'react'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { BootstrapRecoveryPanel } from '@/features/runtime/components/BootstrapRecoveryPanel'
import { useAuthStore } from '@/stores/auth-store'

const LOADING_TIMEOUT_MS = 20_000

export function LoadingLayout() {
  const phase = useAuthStore((state) => state.phase)
  const errorMessage = useAuthStore((state) => state.errorMessage)
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())
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
    }, LOADING_TIMEOUT_MS)

    return () => {
      window.clearTimeout(timer)
    }
  }, [isAuthenticated, phase])

  if (phase === 'error') {
    return (
      <BootstrapRecoveryPanel
        message={errorMessage}
        technicalMessage={errorMessage ?? undefined}
      />
    )
  }

  return <LoadingOverlay label="Loading HEOS…" />
}
