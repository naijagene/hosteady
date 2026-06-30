import type { ActivityEntry } from '@/api/types/activity'

export function getActivityTitle(entry: ActivityEntry): string {
  return entry.summary ?? `${entry.action} ${entry.entity?.label ?? entry.entity?.type ?? 'resource'}`.trim()
}

export function getActivityDescription(entry: ActivityEntry): string {
  const actor = entry.actor?.display_name ?? entry.actor?.email ?? 'System'
  const entity = entry.entity?.label ?? entry.entity?.type ?? 'resource'
  return `${actor} · ${entity}`
}

export function formatActivityTimestamp(value?: string | null): string {
  if (!value) return 'Unknown time'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  return date.toLocaleString()
}

export function formatActivityDateKey(value?: string | null): string {
  if (!value) return 'Unknown date'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return 'Unknown date'
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' })
}
