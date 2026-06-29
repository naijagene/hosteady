export type WorkflowInboxTab =
  | 'assigned'
  | 'approvals'
  | 'running'
  | 'completed'
  | 'failed'
  | 'all'

export interface WorkflowInboxCounts {
  assigned: number
  approvals: number
  running: number
  completed: number
  failed: number
  all: number
}
