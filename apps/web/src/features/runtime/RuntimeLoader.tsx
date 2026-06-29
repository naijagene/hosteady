import { type ReactNode, useEffect } from 'react'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { useAuthStore } from '@/stores/auth-store'
import { HydratedRuntimeProvider } from './HydratedRuntimeProvider'

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

  if (!isAuthenticated) {
    return <>{children}</>
  }

  if (loading && (phase === 'restoring' || phase === 'bootstrapping' || phase === 'hydrating')) {
    return <LoadingOverlay label="Preparing HEOS workspace…" />
  }

  if (errorMessage && phase === 'error') {
    return (
      <div className="flex h-full flex-col items-center justify-center gap-2 p-8 text-sm">
        <p className="font-medium text-destructive">Runtime unavailable</p>
        <p className="text-muted-foreground">{errorMessage}</p>
      </div>
    )
  }

  if (!hasTenantScope && phase !== 'ready') {
    return <LoadingOverlay label="Resolving tenant scope…" />
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
