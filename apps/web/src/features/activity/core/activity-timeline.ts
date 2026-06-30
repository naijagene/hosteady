import type { ActivityEntry, ActivityTimeline, AuditEntry, HistoryEntry } from '@/api/types/activity'
import { formatActivityDateKey } from './activity-normalizer'

export function mergeActivityEntries(...lists: ActivityEntry[][]): ActivityEntry[] {
  const map = new Map<string, ActivityEntry>()
  for (const list of lists) {
    for (const entry of list) {
      map.set(entry.public_id, entry)
    }
  }
  return Array.from(map.values())
}

export function sortActivityEntries(entries: ActivityEntry[]): ActivityEntry[] {
  return [...entries].sort((left, right) => {
    const leftTime = left.occurred_at ? new Date(left.occurred_at).getTime() : 0
    const rightTime = right.occurred_at ? new Date(right.occurred_at).getTime() : 0
    return rightTime - leftTime
  })
}

export function groupActivityByDate(entries: ActivityEntry[]): ActivityTimeline[] {
  const groups = new Map<string, ActivityEntry[]>()
  for (const entry of sortActivityEntries(entries)) {
    const key = formatActivityDateKey(entry.occurred_at)
    groups.set(key, [...(groups.get(key) ?? []), entry])
  }
  return Array.from(groups.entries()).map(([date, items]) => ({ date, items }))
}

export function buildTimeline(entries: Array<ActivityEntry | AuditEntry | HistoryEntry>, mode: 'compact' | 'full' = 'full'): ActivityTimeline[] {
  const safeEntries = entries.filter((entry) => entry && typeof entry.public_id === 'string')
  const grouped = groupActivityByDate(safeEntries)
  if (mode === 'compact') {
    return grouped.map((group) => ({ ...group, items: group.items.slice(0, 5) }))
  }
  return grouped
}
