import type { Approval, HumanTask, WorkflowInstance } from '@/api/types/workflows'

export function getTaskDisplayTitle(task: HumanTask): string {
  return task.title || task.public_id
}

export function getApprovalDisplayTitle(approval: Approval): string {
  return approval.title || approval.public_id
}

export function getInstanceDisplayTitle(instance: WorkflowInstance): string {
  return instance.definition_name || instance.workflow_key || instance.public_id
}

export function formatWorkflowDate(value?: string | null): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

export function formatWorkflowDuration(durationMs?: number | null): string {
  if (durationMs === null || durationMs === undefined) {
    return '—'
  }

  if (durationMs < 1000) {
    return `${durationMs}ms`
  }

  return `${Math.round(durationMs / 1000)}s`
}
