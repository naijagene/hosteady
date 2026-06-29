import { buildWorkflowTimeline, type WorkflowTimelineEntry } from '../core/workflow-timeline'
import { formatWorkflowDate } from '../core/workflow-normalizer'
import { WorkflowStatusBadge } from './WorkflowStatusBadge'
import type {
  HumanTaskComment,
  HumanTaskHistory,
  WorkflowExecutionEvent,
  WorkflowExecutionLog,
  WorkflowExecutionStep,
  WorkflowInstanceHistory,
} from '@/api/types/workflows'

interface WorkflowTimelineProps {
  steps?: WorkflowExecutionStep[]
  events?: WorkflowExecutionEvent[]
  logs?: WorkflowExecutionLog[]
  comments?: HumanTaskComment[]
  history?: HumanTaskHistory[]
  instanceHistory?: WorkflowInstanceHistory | null
}

export function WorkflowTimeline(props: WorkflowTimelineProps) {
  const entries = buildWorkflowTimeline({
    ...props,
    instanceHistory: props.instanceHistory ?? undefined,
  })

  if (entries.length === 0) {
    return (
      <p className="text-sm text-muted-foreground" data-testid="workflow-timeline-empty" role="status">
        No timeline entries available.
      </p>
    )
  }

  return (
    <ol className="space-y-3" data-testid="workflow-timeline" aria-label="Workflow timeline">
      {entries.map((entry) => (
        <TimelineItem key={entry.id} entry={entry} />
      ))}
    </ol>
  )
}

function TimelineItem({ entry }: { entry: WorkflowTimelineEntry }) {
  return (
    <li className="rounded-md border border-border bg-card p-3">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-foreground">{entry.title}</p>
          {entry.subtitle ? <p className="text-xs text-muted-foreground">{entry.subtitle}</p> : null}
          <p className="mt-1 text-xs text-muted-foreground">{formatWorkflowDate(entry.occurred_at)}</p>
        </div>
        {entry.status ? <WorkflowStatusBadge status={entry.status} /> : null}
      </div>
    </li>
  )
}
