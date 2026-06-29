import { useQuery } from '@tanstack/react-query'
import { type ReactNode, useMemo } from 'react'
import {
  fetchPersonalizationRuntime,
  fetchWorkspaceRuntime,
} from '@/api/endpoints/runtime'
import { useAuthStore } from '@/stores/auth-store'
import { useSessionStore } from '@/stores/session-store'
import { RuntimeContextProvider } from './RuntimeContextProvider'
import type { RuntimeBundle } from './runtime-context'

interface RuntimeLoaderProps {
  children: ReactNode
}

export function RuntimeLoader({ children }: RuntimeLoaderProps) {
  const isAuthenticated = useAuthStore((state) => state.isAuthenticated())
  const organizationPublicId = useSessionStore(
    (state) => state.organizationPublicId,
  )
  const workspacePublicId = useSessionStore((state) => state.workspacePublicId)
  const canLoadRuntime = isAuthenticated && Boolean(organizationPublicId)

  const workspaceQuery = useQuery({
    queryKey: [
      'runtime',
      'workspace',
      organizationPublicId,
      workspacePublicId,
    ],
    queryFn: fetchWorkspaceRuntime,
    enabled: canLoadRuntime,
    retry: 1,
  })

  const personalizationQuery = useQuery({
    queryKey: [
      'runtime',
      'personalization',
      organizationPublicId,
      workspacePublicId,
    ],
    queryFn: fetchPersonalizationRuntime,
    enabled: canLoadRuntime,
    retry: 1,
  })

  const value = useMemo<RuntimeBundle>(() => {
    const isLoading =
      canLoadRuntime &&
      (workspaceQuery.isLoading || personalizationQuery.isLoading)
    const isError = workspaceQuery.isError || personalizationQuery.isError
    const errorMessage =
      (workspaceQuery.error as Error | undefined)?.message ??
      (personalizationQuery.error as Error | undefined)?.message ??
      null

    return {
      workspace: workspaceQuery.data ?? null,
      personalization: personalizationQuery.data ?? null,
      isLoading,
      isError,
      errorMessage,
    }
  }, [
    canLoadRuntime,
    personalizationQuery.data,
    personalizationQuery.error,
    personalizationQuery.isError,
    personalizationQuery.isLoading,
    workspaceQuery.data,
    workspaceQuery.error,
    workspaceQuery.isError,
    workspaceQuery.isLoading,
  ])

  return (
    <RuntimeContextProvider value={value}>
      {canLoadRuntime && value.isLoading ? (
        <div className="flex h-full items-center justify-center p-8 text-sm text-muted-foreground">
          Loading platform runtime…
        </div>
      ) : canLoadRuntime && value.isError ? (
        <div className="flex h-full flex-col items-center justify-center gap-2 p-8 text-sm">
          <p className="font-medium text-destructive">Runtime unavailable</p>
          <p className="text-muted-foreground">
            {value.errorMessage ?? 'Unable to load workspace runtime.'}
          </p>
        </div>
      ) : (
        children
      )}
    </RuntimeContextProvider>
  )
}
