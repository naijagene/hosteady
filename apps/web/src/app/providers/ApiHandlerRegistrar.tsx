import { useEffect } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { configureApiClientHandlers } from '@/api/client'
import { useAuthStore } from '@/stores/auth-store'

export function ApiHandlerRegistrar() {
  const navigate = useNavigate()

  useEffect(() => {
    configureApiClientHandlers({
      onUnauthorized: () => {
        useAuthStore.getState().clearAuth()
        void navigate({ to: '/login', replace: true, search: { redirect: undefined } })
      },
      onForbidden: () => {
        void navigate({ to: '/forbidden', replace: true })
      },
    })
  }, [navigate])

  return null
}
