import { useEffect } from 'react'
import { ErrorBoundary } from '@/components/errors/ErrorBoundary'
import { useAuthStore } from '@/stores/auth-store'
import { ApiHandlerRegistrar } from './ApiHandlerRegistrar'
import { QueryProvider } from './QueryProvider'

interface AppProvidersProps {
  children: React.ReactNode
}

export function AppProviders({ children }: AppProvidersProps) {
  const restore = useAuthStore((state) => state.restore)
  const accessToken = useAuthStore((state) => state.accessToken)

  useEffect(() => {
    if (accessToken) {
      void restore()
    }
  }, [accessToken, restore])

  return (
    <ErrorBoundary>
      <QueryProvider>
        <ApiHandlerRegistrar />
        {children}
      </QueryProvider>
    </ErrorBoundary>
  )
}
