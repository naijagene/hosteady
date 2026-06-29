import { useContext } from 'react'
import { RuntimeContext } from './runtime-context'

export function useRuntimeContext() {
  return useContext(RuntimeContext)
}
