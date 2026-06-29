import type { HumanTask } from '@/api/types/workflows'
import { formatWorkflowDate } from './workflow-normalizer'

export function isTaskOverdue(task: HumanTask): boolean {
  if (!task.due_at) {
    return false
  }

  const due = new Date(task.due_at)
  return !Number.isNaN(due.getTime()) && due.getTime() < Date.now() && task.status !== 'completed'
}

export function getTaskSlaLabel(task: HumanTask): string | null {
  if (isTaskOverdue(task)) {
    return 'Overdue'
  }

  if (task.due_at) {
    return `Due ${formatWorkflowDate(task.due_at)}`
  }

  return null
}
