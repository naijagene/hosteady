import { useAuthStore } from '@/stores/auth-store'

export function ApplicationGuard({ children }: { children: React.ReactNode }) {
  const application = useAuthStore((state) => state.application)
  const hydratedRuntime = useAuthStore((state) => state.hydratedRuntime)

  if (
    !application?.public_id &&
    !hydratedRuntime?.workspaceRuntime?.active_application
  ) {
    return <>{children}</>
  }

  return <>{children}</>
}
