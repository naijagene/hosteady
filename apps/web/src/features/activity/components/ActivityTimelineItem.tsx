import type { ActivityEntry } from '@/api/types/activity'
import { formatActivityTimestamp, getActivityTitle } from '../core/activity-normalizer'
import { resolveActivityIcon } from '../core/activity-icons'
import { ActivitySeverityBadge } from './ActivitySeverityBadge'

interface ActivityTimelineItemProps {
  entry: ActivityEntry
  compact?: boolean
  onSelect?: (entry: ActivityEntry) => void
}

export function ActivityTimelineItem({ entry, compact = false, onSelect }: ActivityTimelineItemProps) {
  const icon = resolveActivityIcon(entry.entity?.type, entry.action)
  return (
    <li className="relative pl-8" data-testid={`activity-timeline-item-${entry.public_id}`}>
      <span className="absolute left-0 top-1 inline-flex h-5 w-5 items-center justify-center rounded-full border border-border bg-background text-[9px] uppercase">
        {icon.slice(0, 2)}
      </span>
      <button type="button" className="w-full text-left" onClick={() => onSelect?.(entry)}>
        <div className="flex flex-wrap items-center gap-2">
          <p className="text-sm font-medium text-foreground">{getActivityTitle(entry)}</p>
          {!compact ? <ActivitySeverityBadge severity={entry.severity} /> : null}
        </div>
        {!compact ? (
          <p className="mt-1 text-xs text-muted-foreground">{entry.action}</p>
        ) : null}
        <time className="mt-1 block text-xs text-muted-foreground" dateTime={entry.occurred_at ?? undefined}>
          {formatActivityTimestamp(entry.occurred_at)}
        </time>
      </button>
    </li>
  )
}
