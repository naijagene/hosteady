import { useEffect } from 'react'
import { useNavigate } from '@tanstack/react-router'
import { HEOS_AUTH_STORAGE_KEY } from '@/features/auth/core/session-reset'
import { useAuthStore } from '@/stores/auth-store'

export function useMultiTabSessionSync(): void {
  const navigate = useNavigate()

  useEffect(() => {
    if (typeof window === 'undefined') {
      return
    }

    const handleStorage = (event: StorageEvent) => {
      if (event.key !== HEOS_AUTH_STORAGE_KEY) {
        return
      }

      if (event.newValue === null || event.newValue === '') {
        useAuthStore.getState().clearAuth()
        void navigate({ to: '/login', replace: true, search: { redirect: undefined } })
      }
    }

    window.addEventListener('storage', handleStorage)
    return () => {
      window.removeEventListener('storage', handleStorage)
    }
  }, [navigate])
}
