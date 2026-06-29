import { createContext } from 'react'
import type { UiComponent, UiRenderPayload } from '@/api/types/ui'

export interface RendererContextValue {
  permissions: string[]
  devMode: boolean
  moduleKey?: string
  pageKey?: string
  payload?: UiRenderPayload | null
  componentIndex: Map<string, UiComponent>
}

export const RendererContext = createContext<RendererContextValue | null>(null)
