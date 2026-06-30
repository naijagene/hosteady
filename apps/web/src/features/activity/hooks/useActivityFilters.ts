import { useMemo, useState } from 'react'
import type { ActivityQueryPayload } from '@/api/types/activity'
import { createInitialActivityQuery, mergeActivityQuery } from '../core/activity-query'
import { getTabQueryPatch } from '../core/activity-filters'

export function useActivityFilters(initial?: Partial<ActivityQueryPayload>) {
  const [query, setQuery] = useState<ActivityQueryPayload>(() => createInitialActivityQuery(initial))
  const [activeTab, setActiveTab] = useState('recent')

  const setSearch = (search: string) => setQuery((current) => mergeActivityQuery(current, { search, page: 1 }))
  const setPage = (page: number) => setQuery((current) => mergeActivityQuery(current, { page }))
  const setTab = (tab: string) => {
    setActiveTab(tab)
    setQuery(mergeActivityQuery(createInitialActivityQuery(initial), getTabQueryPatch(tab)))
  }

  const filters = useMemo(
    () => ({
      entityType: query.entity_type,
      severity: query.severity,
      action: query.action,
      actor: query.actor_user_public_id ?? query.actor_membership_public_id,
      workspace: query.workspace_public_id,
    }),
    [query],
  )

  return {
    query,
    activeTab,
    filters,
    setQuery,
    setSearch,
    setPage,
    setTab,
  }
}
