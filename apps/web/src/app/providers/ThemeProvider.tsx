import { type ReactNode, useEffect, useMemo } from 'react'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { useAuthStore } from '@/stores/auth-store'

type ThemeMode = 'light' | 'dark' | 'system'

function resolveThemeMode(
  preferences: Record<string, unknown> | undefined,
): ThemeMode {
  const value = preferences?.theme_mode

  if (value === 'dark' || value === 'light' || value === 'system') {
    return value
  }

  return 'system'
}

function applyCssVariables(tokens: Record<string, unknown>, prefix: string): () => void {
  const root = document.documentElement
  const applied: string[] = []

  Object.entries(tokens).forEach(([key, value]) => {
    if (typeof value !== 'string' || value.trim() === '') {
      return
    }

    const cssKey = `--${prefix}-${key.replace(/\./g, '-')}`
    root.style.setProperty(cssKey, value)
    applied.push(cssKey)
  })

  return () => {
    applied.forEach((cssKey) => root.style.removeProperty(cssKey))
  }
}

interface ThemeProviderProps {
  children: ReactNode
}

export function ThemeProvider({ children }: ThemeProviderProps) {
  const runtime = useHydratedRuntime()
  const themePreference = useAuthStore(
    (state) => state.hydratedRuntime?.personalizationRuntime?.preferences,
  )

  const themeMode = useMemo(
    () =>
      resolveThemeMode(
        Array.isArray(themePreference)
          ? Object.fromEntries(
              themePreference.map((entry) => [
                String((entry as Record<string, unknown>).key ?? ''),
                (entry as Record<string, unknown>).value,
              ]),
            )
          : undefined,
      ),
    [themePreference],
  )

  useEffect(() => {
    const media = window.matchMedia('(prefers-color-scheme: dark)')
    const applyMode = () => {
      const resolved =
        themeMode === 'system' ? (media.matches ? 'dark' : 'light') : themeMode
      document.documentElement.dataset.theme = resolved
      document.documentElement.classList.toggle('dark', resolved === 'dark')
    }

    applyMode()
    media.addEventListener('change', applyMode)

    return () => media.removeEventListener('change', applyMode)
  }, [themeMode])

  useEffect(() => {
    const themePayload = (runtime?.themeRuntime?.theme ?? {}) as Record<
      string,
      unknown
    >
    const themeTokens =
      (themePayload.tokens as Record<string, unknown> | undefined) ??
      themePayload
    const brandProfile =
      (runtime?.themeRuntime?.brand_profile as Record<string, unknown> | undefined) ??
      (themePayload.brand as Record<string, unknown> | undefined) ??
      {}
    const override = runtime?.personalizationRuntime?.theme_override ?? {}

    const cleanupTheme = applyCssVariables(themeTokens, 'heos-theme')
    const cleanupBrand = applyCssVariables(brandProfile, 'heos-brand')
    const cleanupOverride = applyCssVariables(override, 'heos-theme-override')

    const logoUrl =
      typeof brandProfile.logo_url === 'string'
        ? brandProfile.logo_url
        : typeof override.logo_url === 'string'
          ? override.logo_url
          : null

    if (logoUrl) {
      document.documentElement.style.setProperty(
        '--heos-brand-logo-url',
        `url("${logoUrl}")`,
      )
    }

    const typography =
      typeof (themeTokens as Record<string, unknown>)['font.family.base'] ===
      'string'
        ? ((themeTokens as Record<string, unknown>)['font.family.base'] as string)
        : typeof brandProfile.font_family === 'string'
          ? brandProfile.font_family
          : null

    if (typography) {
      document.documentElement.style.setProperty('--font-sans', typography)
    }

    return () => {
      cleanupTheme()
      cleanupBrand()
      cleanupOverride()
      document.documentElement.style.removeProperty('--heos-brand-logo-url')
    }
  }, [runtime])

  return <>{children}</>
}
