import { createContext } from 'react'
import type { ApiRecord } from '@/api/types/api'

export interface NavigationContextValue {
  navigation: ApiRecord
  overrides: ApiRecord
  shortcuts: ApiRecord[]
}

export const NavigationContext = createContext<NavigationContextValue>({
  navigation: {},
  overrides: {},
  shortcuts: [],
})
