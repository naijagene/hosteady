import { createContext } from 'react'
import type { ApiRecord } from '@/api/types/api'
import type { NavigationMenuResponse } from '@/api/types/runtime'

export interface NavigationContextValue {
  menus: NavigationMenuResponse[]
  navigation: NavigationMenuResponse
  overrides: ApiRecord
  shortcuts: ApiRecord[]
}

export const defaultNavigationContext: NavigationContextValue = {
  menus: [],
  navigation: {
    menu_key: 'main',
    label: 'Main',
    groups: [],
    metadata: {},
  },
  overrides: {},
  shortcuts: [],
}

export const NavigationContext = createContext<NavigationContextValue>(
  defaultNavigationContext,
)
