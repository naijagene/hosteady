import { useMemo, type ReactNode } from 'react'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { NavigationContext } from './navigation-context'
import type { NavigationMenuResponse } from '@/api/types/runtime'

export function NavigationProvider({ children }: { children: ReactNode }) {
  const runtime = useHydratedRuntime()

  const value = useMemo(() => {
    const menus = runtime?.navigationMenus ?? []
    const overrides = runtime?.personalizationRuntime?.navigation_overrides ?? {}
    const shortcuts = runtime?.personalizationRuntime?.shortcuts ?? []

    return {
      menus,
      navigation: menus[0] ?? ({} as NavigationMenuResponse),
      overrides,
      shortcuts,
    }
  }, [runtime])

  return (
    <NavigationContext.Provider value={value}>
      {children}
    </NavigationContext.Provider>
  )
}
