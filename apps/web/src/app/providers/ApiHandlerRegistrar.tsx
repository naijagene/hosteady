import { useEffect } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { configureApiClientHandlers } from '@/api/client'
import { resetSession } from '@/features/auth/core/session-reset'

export function ApiHandlerRegistrar() {
  const navigate = useNavigate()

  useEffect(() => {
    configureApiClientHandlers({
      onUnauthorized: () => {
        void resetSession().then(() => {
          void navigate({ to: '/login', replace: true, search: { redirect: undefined } })
        })
      },
      onForbidden: () => {
        void navigate({ to: '/forbidden', replace: true })
      },
    })
  }, [navigate])

  return null
}
