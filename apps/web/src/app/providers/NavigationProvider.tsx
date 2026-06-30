import { useMemo, type ReactNode } from 'react'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import {
  hasNavigationItems,
} from '@/features/runtime/core/normalize-navigation'
import { buildPlatformFallbackNavigation } from '@/features/runtime/core/platform-fallback-navigation'
import { NavigationContext } from './navigation-context'
import type { NavigationMenuResponse } from '@/api/types/runtime'

export function NavigationProvider({ children }: { children: ReactNode }) {
  const runtime = useHydratedRuntime()

  const value = useMemo(() => {
    const backendMenus = runtime?.navigationMenus ?? []
    const fallbackMenus = buildPlatformFallbackNavigation()
    const usingFallbackNavigation = !hasNavigationItems(backendMenus)
    const menus = usingFallbackNavigation ? fallbackMenus : backendMenus
    const overrides = runtime?.personalizationRuntime?.navigation_overrides ?? {}
    const shortcuts = runtime?.personalizationRuntime?.shortcuts ?? []

    return {
      menus,
      backendMenus,
      fallbackMenus,
      usingFallbackNavigation,
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
