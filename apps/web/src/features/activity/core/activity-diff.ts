import type { ActivityChangeSet } from '@/api/types/activity'

export function sanitizeChangeValue(value: unknown, sensitive = false): string {
  if (sensitive) return '[redacted]'
  if (value === null || value === undefined) return '—'
  if (typeof value === 'string') return value
  if (typeof value === 'number' || typeof value === 'boolean') return String(value)
  try {
    return JSON.stringify(value)
  } catch {
    return '[unavailable]'
  }
}

export function normalizeChangeSets(raw: unknown): ActivityChangeSet[] {
  if (!Array.isArray(raw)) return []
  return raw.map((entry) => {
    const item = entry && typeof entry === 'object' ? (entry as ActivityChangeSet) : {}
    return {
      field: item.field ?? null,
      before: item.before,
      after: item.after,
      change_type: item.change_type ?? null,
      sensitive: Boolean(item.sensitive),
    }
  })
}

export function getChangeSetSummary(changes: ActivityChangeSet[] = []): string {
  if (changes.length === 0) return 'No visible changes'
  return `${changes.length} field${changes.length === 1 ? '' : 's'} changed`
}
