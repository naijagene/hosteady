import { Navigate } from '@tanstack/react-router'
import { useAuthStore } from '@/stores/auth-store'

export function GuestGuard({ children }: { children: React.ReactNode }) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())
  const phase = useAuthStore((state) => state.phase)

  if (isAuthenticated && phase === 'ready') {
    return <Navigate to="/" replace />
  }

  return <>{children}</>
}
