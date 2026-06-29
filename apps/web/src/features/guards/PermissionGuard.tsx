import { Navigate } from '@tanstack/react-router'
import { useAuthStore } from '@/stores/auth-store'

interface PermissionGuardProps {
  permission: string
  children: React.ReactNode
  fallbackTo?: '/forbidden' | '/unauthorized'
}

export function PermissionGuard({
  permission,
  children,
  fallbackTo = '/forbidden',
}: PermissionGuardProps) {
  const hasPermission = useAuthStore((state) => state.hasPermission(permission))
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())

  if (!isAuthenticated) {
    return <Navigate to="/unauthorized" replace />
  }

  if (!hasPermission) {
    return <Navigate to={fallbackTo} replace />
  }

  return <>{children}</>
}
