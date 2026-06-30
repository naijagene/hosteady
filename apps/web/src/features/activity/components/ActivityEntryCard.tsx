import type { ActivityEntry } from '@/api/types/activity'
import { labelActivitySource } from '@/api/endpoints/activity'
import { formatActivityTimestamp, getActivityDescription, getActivityTitle } from '../core/activity-normalizer'
import { ActivityActorBadge } from './ActivityActorBadge'
import { ActivityEntityBadge } from './ActivityEntityBadge'
import { ActivitySeverityBadge } from './ActivitySeverityBadge'

interface ActivityEntryCardProps {
  entry: ActivityEntry
  onSelect?: (entry: ActivityEntry) => void
}

export function ActivityEntryCard({ entry, onSelect }: ActivityEntryCardProps) {
  return (
    <button
      type="button"
      className="flex w-full flex-col gap-2 rounded-lg border border-border bg-card p-4 text-left hover:bg-muted/40"
      data-testid={`activity-entry-${entry.public_id}`}
      onClick={() => onSelect?.(entry)}
    >
      <div className="flex flex-wrap items-center gap-2">
        <ActivitySeverityBadge severity={entry.severity} />
        <ActivityEntityBadge type={entry.entity?.type} label={entry.entity?.label ?? entry.entity?.type} />
        <span className="text-[10px] uppercase text-muted-foreground">{labelActivitySource(entry.source)}</span>
      </div>
      <div>
        <p className="text-sm font-medium text-foreground">{getActivityTitle(entry)}</p>
        <p className="mt-1 text-xs text-muted-foreground">{getActivityDescription(entry)}</p>
      </div>
      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <ActivityActorBadge actor={entry.actor} />
        <time dateTime={entry.occurred_at ?? undefined}>{formatActivityTimestamp(entry.occurred_at)}</time>
      </div>
    </button>
  )
}
