import { useContext } from 'react'
import { NavigationContext } from './navigation-context'

export function useNavigationContext() {
  return useContext(NavigationContext)
}
