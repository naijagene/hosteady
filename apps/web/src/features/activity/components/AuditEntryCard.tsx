import type { AuditEntry } from '@/api/types/activity'
import { formatActivityTimestamp, getActivityTitle } from '../core/activity-normalizer'
import { ActivityActorBadge } from './ActivityActorBadge'
import { ActivityChangeSetViewer } from './ActivityChangeSetViewer'
import { ActivityEntityBadge } from './ActivityEntityBadge'
import { ActivitySeverityBadge } from './ActivitySeverityBadge'

interface AuditEntryCardProps {
  entry: AuditEntry
  expanded?: boolean
  onToggle?: () => void
}

export function AuditEntryCard({ entry, expanded = false, onToggle }: AuditEntryCardProps) {
  return (
    <article className="rounded-lg border border-border bg-card p-4" data-testid={`audit-entry-${entry.public_id}`}>
      <div className="flex flex-wrap items-center gap-2">
        <ActivitySeverityBadge severity={entry.severity} />
        <ActivityEntityBadge type={entry.entity?.type} label={entry.entity?.label ?? entry.action} />
      </div>
      <h3 className="mt-2 text-sm font-medium text-foreground">{getActivityTitle(entry)}</h3>
      <div className="mt-2 flex flex-wrap gap-3 text-xs text-muted-foreground">
        <ActivityActorBadge actor={entry.actor} />
        <time dateTime={entry.occurred_at ?? undefined}>{formatActivityTimestamp(entry.occurred_at)}</time>
        {entry.module_key ? <span>Module: {entry.module_key}</span> : null}
      </div>
      {entry.ip_address || entry.user_agent ? (
        <p className="mt-2 text-xs text-muted-foreground">
          {entry.ip_address ? `IP ${entry.ip_address}` : null}
          {entry.user_agent ? ` · ${entry.user_agent}` : null}
        </p>
      ) : null}
      <button type="button" className="mt-3 text-xs text-primary" onClick={onToggle}>
        {expanded ? 'Hide details' : 'Show details'}
      </button>
      {expanded ? (
        <div className="mt-3 space-y-3">
          <ActivityChangeSetViewer changes={entry.changes} />
          {entry.metadata && Object.keys(entry.metadata).length > 0 ? (
            <details className="rounded-md border border-border p-3 text-xs">
              <summary className="cursor-pointer font-medium">Metadata</summary>
              <pre className="mt-2 overflow-auto whitespace-pre-wrap text-muted-foreground">
                {JSON.stringify(entry.metadata, null, 2)}
              </pre>
            </details>
          ) : null}
        </div>
      ) : null}
    </article>
  )
}
