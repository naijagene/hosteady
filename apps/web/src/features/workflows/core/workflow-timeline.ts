import type {
  HumanTaskHistory,
  WorkflowExecutionEvent,
  WorkflowExecutionLog,
  WorkflowExecutionStep,
  WorkflowInstanceHistory,
} from '@/api/types/workflows'
import type { HumanTaskComment } from '@/api/types/workflows'

export interface WorkflowTimelineEntry {
  id: string
  kind: 'step' | 'event' | 'log' | 'comment' | 'history'
  title: string
  subtitle?: string | null
  occurred_at: string
  status?: string | null
}

function toTimestamp(value?: string | null): number {
  if (!value) {
    return 0
  }

  const time = new Date(value).getTime()
  return Number.isNaN(time) ? 0 : time
}

export function buildWorkflowTimeline(options: {
  steps?: WorkflowExecutionStep[]
  events?: WorkflowExecutionEvent[]
  logs?: WorkflowExecutionLog[]
  comments?: HumanTaskComment[]
  history?: HumanTaskHistory[]
  instanceHistory?: WorkflowInstanceHistory
}): WorkflowTimelineEntry[] {
  const entries: WorkflowTimelineEntry[] = []

  for (const step of options.steps ?? options.instanceHistory?.steps ?? []) {
    entries.push({
      id: step.public_id ?? `${step.node_id}-${step.started_at}`,
      kind: 'step',
      title: step.node_id ? `Step ${step.node_id}` : 'Execution step',
      subtitle: step.node_type,
      occurred_at: step.completed_at ?? step.started_at ?? '',
      status: step.status,
    })
  }

  for (const event of options.events ?? options.instanceHistory?.events ?? []) {
    entries.push({
      id: `${event.event_type}-${event.occurred_at}`,
      kind: 'event',
      title: event.summary ?? event.event_type,
      subtitle: event.event_type,
      occurred_at: event.occurred_at,
    })
  }

  for (const log of options.logs ?? options.instanceHistory?.logs ?? []) {
    entries.push({
      id: `${log.message}-${log.occurred_at}`,
      kind: 'log',
      title: log.message,
      subtitle: log.level,
      occurred_at: log.occurred_at ?? '',
    })
  }

  for (const comment of options.comments ?? []) {
    entries.push({
      id: comment.public_id,
      kind: 'comment',
      title: comment.body,
      subtitle: 'Comment',
      occurred_at: comment.created_at ?? '',
    })
  }

  for (const item of options.history ?? []) {
    entries.push({
      id: `${item.event_type}-${item.occurred_at}`,
      kind: 'history',
      title: item.summary ?? item.event_type,
      subtitle: item.event_type,
      occurred_at: item.occurred_at,
    })
  }

  return entries.sort((left, right) => toTimestamp(right.occurred_at) - toTimestamp(left.occurred_at))
}
