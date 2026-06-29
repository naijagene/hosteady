import { useMemo, type ReactNode } from 'react'
import type { UiComponent } from '@/api/types/ui'
import { RendererContext, type RendererContextValue } from './renderer-context'

interface RendererContextProviderProps {
  children: ReactNode
  permissions?: string[]
  moduleKey?: string
  pageKey?: string
  payload?: import('@/api/types/ui').UiRenderPayload | null
  devMode?: boolean
}

export function RendererContextProvider({
  children,
  permissions = [],
  moduleKey,
  pageKey,
  payload = null,
  devMode = import.meta.env.DEV,
}: RendererContextProviderProps) {
  const value = useMemo<RendererContextValue>(() => {
    const componentIndex = new Map<string, UiComponent>()

    payload?.components.forEach((component) => {
      if (component.public_id) {
        componentIndex.set(component.public_id, component)
      }
      if (component.component_key) {
        componentIndex.set(component.component_key, component)
      }
    })

    return {
      permissions,
      devMode,
      moduleKey,
      pageKey,
      payload,
      componentIndex,
    }
  }, [permissions, devMode, moduleKey, pageKey, payload])

  return (
    <RendererContext.Provider value={value}>{children}</RendererContext.Provider>
  )
}

export type { RendererContextValue } from './renderer-context'
