import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { fetchAuditEvents, fetchAuditSummary } from '@/api/endpoints/activity'
import type { ActivityQueryPayload } from '@/api/types/activity'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { canReadAudit, filterActivityByPermission } from '../core/activity-permissions'
import { toActivityQueryError } from '../core/activity-errors'

export function useAuditLog(payload: ActivityQueryPayload = {}) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(() => runtime?.permissions ?? [], [runtime?.permissions])
  const enabled = canReadAudit(permissions)

  const query = useQuery({
    queryKey: ['audit-log', payload, permissions],
    queryFn: () => fetchAuditEvents(payload),
    enabled,
  })

  const summaryQuery = useQuery({
    queryKey: ['audit-summary', payload.occurred_from, payload.occurred_to, payload.workspace_public_id],
    queryFn: () => fetchAuditSummary(payload),
    enabled,
  })

  const items = useMemo(
    () => filterActivityByPermission(query.data?.items ?? [], permissions),
    [permissions, query.data?.items],
  )

  return {
    items,
    summary: summaryQuery.data ?? null,
    enabled,
    isLoading: query.isLoading,
    error: query.error ? toActivityQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
