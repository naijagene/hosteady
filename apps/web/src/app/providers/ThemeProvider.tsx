import { type ReactNode, useEffect, useMemo } from 'react'
import { useRuntimeContext } from '@/features/runtime/use-runtime-context'

interface ThemeProviderProps {
  children: ReactNode
}

export function ThemeProvider({ children }: ThemeProviderProps) {
  const { personalization } = useRuntimeContext()

  const themeOverride = useMemo(
    () => personalization?.theme_override ?? {},
    [personalization?.theme_override],
  )

  useEffect(() => {
    const root = document.documentElement

    Object.entries(themeOverride).forEach(([key, value]) => {
      if (typeof value === 'string' && value.trim() !== '') {
        root.style.setProperty(`--heos-theme-${key}`, value)
      }
    })

    return () => {
      Object.keys(themeOverride).forEach((key) => {
        root.style.removeProperty(`--heos-theme-${key}`)
      })
    }
  }, [themeOverride])

  return <>{children}</>
}
