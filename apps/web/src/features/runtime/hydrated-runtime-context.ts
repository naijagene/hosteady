import { createContext } from 'react'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

export const HydratedRuntimeContext = createContext<HydratedRuntimeBundle | null>(
  null,
)
