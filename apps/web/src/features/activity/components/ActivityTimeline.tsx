import type { ActivityEntry } from '@/api/types/activity'
import type { ActivityTimeline } from '@/api/types/activity'
import { ActivityTimelineItem } from './ActivityTimelineItem'

interface ActivityTimelineProps {
  groups: ActivityTimeline[]
  compact?: boolean
  onSelect?: (entry: ActivityEntry) => void
}

export function ActivityTimelineView({ groups, compact = false, onSelect }: ActivityTimelineProps) {
  if (groups.length === 0) return null

  return (
    <div className="space-y-6" data-testid="activity-timeline">
      {groups.map((group) => (
        <section key={group.date} aria-label={`Activity on ${group.date}`}>
          <h3 className="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{group.date}</h3>
          <ol className="space-y-4 border-l border-border pl-4">
            {group.items.map((entry) => (
              <ActivityTimelineItem key={entry.public_id} entry={entry} compact={compact} onSelect={onSelect} />
            ))}
          </ol>
        </section>
      ))}
    </div>
  )
}
