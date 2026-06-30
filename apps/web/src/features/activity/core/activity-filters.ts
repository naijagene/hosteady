import type { ActivityFilter, ActivityQueryPayload } from '@/api/types/activity'

export function applyActivityFilters(payload: ActivityQueryPayload, filters: ActivityFilter[]): ActivityQueryPayload {
  return { ...payload, filters: [...(payload.filters ?? []), ...filters] }
}

export function serializeActivityFilters(filters: ActivityFilter[] = []): Record<string, string> {
  return filters.reduce<Record<string, string>>((acc, filter) => {
    acc[filter.key] = filter.value
    return acc
  }, {})
}

export function createActivityFilter(key: string, value: string): ActivityFilter {
  return { key, value }
}

export const activityTabFilters: Record<string, Partial<ActivityQueryPayload>> = {
  recent: {},
  audit: { category: 'audit' },
  history: { category: 'system' },
  mine: {},
  workflow: { entity_type: 'workflow' },
  documents: { entity_type: 'document' },
  records: { entity_type: 'record' },
  security: { category: 'security', severity: 'critical' },
}

export function getTabQueryPatch(tab: string): Partial<ActivityQueryPayload> {
  return activityTabFilters[tab] ?? {}
}
