import type { ActivityQueryPayload } from '@/api/types/activity'

export function applyActivitySort(payload: ActivityQueryPayload, key: string, direction: 'asc' | 'desc' = 'desc'): ActivityQueryPayload {
  return { ...payload, sorts: [{ key, direction }] }
}

export function getDefaultActivitySort(): ActivityQueryPayload['sorts'] {
  return [{ key: 'occurred_at', direction: 'desc' }]
}
