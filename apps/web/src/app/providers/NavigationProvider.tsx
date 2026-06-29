import { useMemo, type ReactNode } from 'react'
import { useRuntimeContext } from '@/features/runtime/use-runtime-context'
import { NavigationContext } from './navigation-context'

export function NavigationProvider({ children }: { children: ReactNode }) {
  const { workspace, personalization } = useRuntimeContext()

  const value = useMemo(
    () => ({
      navigation: workspace?.navigation ?? {},
      overrides: personalization?.navigation_overrides ?? {},
      shortcuts: personalization?.shortcuts ?? [],
    }),
    [personalization, workspace],
  )

  return (
    <NavigationContext.Provider value={value}>
      {children}
    </NavigationContext.Provider>
  )
}
