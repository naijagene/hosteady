import { useEffect } from 'react'
import { ErrorBoundary } from '@/components/errors/ErrorBoundary'
import {
  ApiHandlerRegistrar,
} from '@/app/providers/ApiHandlerRegistrar'
import { useMultiTabSessionSync } from '@/app/providers/use-multi-tab-session-sync'
import { useAuthStore } from '@/stores/auth-store'
import { QueryProvider } from './QueryProvider'

interface AppProvidersProps {
  children: React.ReactNode
}

export function AppProviders({ children }: AppProvidersProps) {
  const restore = useAuthStore((state) => state.restore)
  const accessToken = useAuthStore((state) => state.accessToken)
  const phase = useAuthStore((state) => state.phase)

  useMultiTabSessionSync()

  useEffect(() => {
    const finishHydration = useAuthStore.persist.onFinishHydration(() => {
      const state = useAuthStore.getState()

      if (state.accessToken && typeof state.accessToken !== 'string') {
        state.clearAuth()
        return
      }

      if (state.accessToken && state.isSessionExpired()) {
        state.clearAuth()
      }
    })

    return finishHydration
  }, [])

  useEffect(() => {
    if (accessToken && phase !== 'error') {
      void restore()
    }
  }, [accessToken, phase, restore])

  return (
    <ErrorBoundary>
      <QueryProvider>
        <ApiHandlerRegistrar />
        {children}
      </QueryProvider>
    </ErrorBoundary>
  )
}
