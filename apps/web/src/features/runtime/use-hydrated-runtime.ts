import { useContext } from 'react'
import { HydratedRuntimeContext } from './hydrated-runtime-context'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

export function useHydratedRuntime(): HydratedRuntimeBundle | null {
  return useContext(HydratedRuntimeContext)
}

export function useRequiredHydratedRuntime(): HydratedRuntimeBundle {
  const runtime = useHydratedRuntime()

  if (!runtime) {
    throw new Error('Hydrated runtime is not available.')
  }

  return runtime
}
