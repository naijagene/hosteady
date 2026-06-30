import type { ActivityEntry, AuditEntry } from '@/api/types/activity'
import { labelActivitySource } from '@/api/endpoints/activity'
import { formatActivityTimestamp, getActivityDescription, getActivityTitle } from '../core/activity-normalizer'
import { ActivityActorBadge } from './ActivityActorBadge'
import { ActivityChangeSetViewer } from './ActivityChangeSetViewer'
import { ActivityEntityBadge } from './ActivityEntityBadge'
import { ActivitySeverityBadge } from './ActivitySeverityBadge'

interface ActivityDetailDrawerProps {
  entry: ActivityEntry | AuditEntry | null
  open: boolean
  onClose: () => void
  onOpenResource?: (entry: ActivityEntry) => void
}

export function ActivityDetailDrawer({ entry, open, onClose, onOpenResource }: ActivityDetailDrawerProps) {
  if (!open || !entry) return null

  return (
    <aside
      role="dialog"
      aria-modal="true"
      aria-label={`Activity detail ${getActivityTitle(entry)}`}
      className="fixed inset-y-0 right-0 z-40 w-full max-w-xl border-l border-border bg-background p-5 shadow-lg"
      data-testid="activity-detail-drawer"
    >
      <div className="mb-4 flex items-start justify-between gap-3">
        <div>
          <h2 className="text-base font-semibold text-foreground">Activity detail</h2>
          <p className="text-xs text-muted-foreground">{labelActivitySource(entry.source)}</p>
        </div>
        <button type="button" className="text-sm text-muted-foreground" onClick={onClose} aria-label="Close activity detail">
          Close
        </button>
      </div>

      <div className="space-y-4">
        <div className="flex flex-wrap gap-2">
          <ActivitySeverityBadge severity={entry.severity} />
          <ActivityEntityBadge type={entry.entity?.type} label={entry.entity?.label ?? entry.entity?.type} />
        </div>
        <div>
          <p className="text-sm font-medium text-foreground">{getActivityTitle(entry)}</p>
          <p className="mt-1 text-xs text-muted-foreground">{getActivityDescription(entry)}</p>
          <time className="mt-2 block text-xs text-muted-foreground" dateTime={entry.occurred_at ?? undefined}>
            {formatActivityTimestamp(entry.occurred_at)}
          </time>
        </div>
        <ActivityActorBadge actor={entry.actor} />
        <ActivityChangeSetViewer changes={entry.changes} />
        <div className="rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground">
          Comments and notes are not available yet.
        </div>
        {onOpenResource ? (
          <button
            type="button"
            className="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"
            onClick={() => onOpenResource(entry)}
          >
            Open related resource
          </button>
        ) : null}
      </div>
    </aside>
  )
}
