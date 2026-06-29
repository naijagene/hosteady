import { HydratedRuntimeContext } from './hydrated-runtime-context'
import { useAuthStore } from '@/stores/auth-store'

export function HydratedRuntimeProvider({
  children,
}: {
  children: React.ReactNode
}) {
  const runtime = useAuthStore((state) => state.hydratedRuntime)

  return (
    <HydratedRuntimeContext.Provider value={runtime}>
      {children}
    </HydratedRuntimeContext.Provider>
  )
}
