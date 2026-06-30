import { useEffect, useRef } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { LoadingOverlay } from '@/components/loading/LoadingOverlay'
import { useAuthStore } from '@/stores/auth-store'

export function LogoutPage() {
  const navigate = useNavigate()
  const logout = useAuthStore((state) => state.logout)
  const startedRef = useRef(false)

  useEffect(() => {
    if (startedRef.current) {
      return
    }

    startedRef.current = true

    void (async () => {
      await logout()
      await navigate({ to: '/login', replace: true, search: { redirect: undefined } })
    })()
  }, [logout, navigate])

  return <LoadingOverlay label="Signing out…" />
}
