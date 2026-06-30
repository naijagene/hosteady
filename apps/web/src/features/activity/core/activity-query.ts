import type { ActivityQueryPayload } from '@/api/types/activity'

export function createInitialActivityQuery(overrides?: Partial<ActivityQueryPayload>): ActivityQueryPayload {
  return {
    page: 1,
    per_page: 25,
    search: '',
    filters: [],
    sorts: [{ key: 'occurred_at', direction: 'desc' }],
    metadata: { source: 'web', binding: 'activity_center' },
    ...overrides,
  }
}

export function mergeActivityQuery(current: ActivityQueryPayload, patch: Partial<ActivityQueryPayload>): ActivityQueryPayload {
  return { ...current, ...patch }
}

export function shouldQueryActivity(search?: string): boolean {
  return Boolean(search?.trim())
}
