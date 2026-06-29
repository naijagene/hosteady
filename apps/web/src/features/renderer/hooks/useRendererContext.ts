import { useContext, useMemo } from 'react'
import { RendererContext } from '../core/renderer-context'
import type { UiComponent } from '@/api/types/ui'
import {
  resolveComponentBinding,
  type ResolvedBinding,
} from '../core/binding-resolver'

export function useRendererContext() {
  const context = useContext(RendererContext)

  if (!context) {
    throw new Error('useRendererContext must be used within RendererContextProvider')
  }

  return context
}

export function useOptionalRendererContext() {
  return useContext(RendererContext)
}

export function useComponentBinding(component: UiComponent): ResolvedBinding | null {
  const context = useOptionalRendererContext()

  return useMemo(
    () => resolveComponentBinding(component, context?.moduleKey),
    [component, context?.moduleKey],
  )
}
