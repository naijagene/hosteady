import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { safeFetchActivityFeed } from '@/api/endpoints/activity'
import type { ActivityQueryPayload } from '@/api/types/activity'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { filterActivityByPermission } from '../core/activity-permissions'
import { buildRuntimeActivityPlaceholders } from '../core/activity-runtime'
import { toActivityQueryError } from '../core/activity-errors'

export function useActivityFeed(payload: ActivityQueryPayload = {}) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(() => runtime?.permissions ?? [], [runtime?.permissions])

  const query = useQuery({
    queryKey: ['activity-feed', payload, permissions],
    queryFn: async () => {
      const result = await safeFetchActivityFeed(payload)
      if (result.items.length > 0) return result
      const placeholders = buildRuntimeActivityPlaceholders(runtime)
      return {
        ...result,
        items: placeholders,
        total: placeholders.length,
        source: 'runtime' as const,
      }
    },
  })

  const items = useMemo(
    () => filterActivityByPermission(query.data?.items ?? [], permissions),
    [permissions, query.data?.items],
  )

  return {
    items,
    source: query.data?.source ?? 'local',
    page: query.data?.page ?? 1,
    perPage: query.data?.per_page ?? payload.per_page ?? 25,
    total: query.data?.total ?? items.length,
    hasMore: query.data?.has_more ?? false,
    isLoading: query.isLoading,
    error: query.error ? toActivityQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
