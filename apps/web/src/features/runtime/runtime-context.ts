import { createContext } from 'react'
import type {
  PersonalizationRuntimeResponse,
  WorkspaceRuntimeResponse,
} from '@/api/types/runtime'

export interface RuntimeBundle {
  workspace: WorkspaceRuntimeResponse | null
  personalization: PersonalizationRuntimeResponse | null
  isLoading: boolean
  isError: boolean
  errorMessage: string | null
}

export const defaultRuntime: RuntimeBundle = {
  workspace: null,
  personalization: null,
  isLoading: false,
  isError: false,
  errorMessage: null,
}

export const RuntimeContext = createContext<RuntimeBundle>(defaultRuntime)
