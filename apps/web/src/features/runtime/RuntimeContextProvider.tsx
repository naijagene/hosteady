import { type ReactNode } from 'react'
import { RuntimeContext } from './runtime-context'
import type { RuntimeBundle } from './runtime-context'

export function RuntimeContextProvider({
  value,
  children,
}: {
  value: RuntimeBundle
  children: ReactNode
}) {
  return (
    <RuntimeContext.Provider value={value}>{children}</RuntimeContext.Provider>
  )
}
