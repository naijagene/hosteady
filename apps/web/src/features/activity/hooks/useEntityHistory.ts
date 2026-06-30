import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { fetchEntityHistory } from '@/api/endpoints/activity'
import type { ActivityQueryPayload } from '@/api/types/activity'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { filterActivityByPermission } from '../core/activity-permissions'
import { toActivityQueryError } from '../core/activity-errors'

export function useEntityHistory(entityType: string, entityPublicId: string, payload: ActivityQueryPayload = {}) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(() => runtime?.permissions ?? [], [runtime?.permissions])

  const query = useQuery({
    queryKey: ['entity-history', entityType, entityPublicId, payload, permissions],
    queryFn: () => fetchEntityHistory(entityType, entityPublicId, payload),
    enabled: Boolean(entityType && entityPublicId),
  })

  const items = useMemo(
    () => filterActivityByPermission(query.data?.items ?? [], permissions),
    [permissions, query.data?.items],
  )

  return {
    items,
    source: query.data?.source ?? 'local',
    isLoading: query.isLoading,
    error: query.error ? toActivityQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
