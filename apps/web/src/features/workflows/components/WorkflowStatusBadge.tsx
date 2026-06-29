import { getWorkflowStatusLabel, getWorkflowStatusTone } from '../core/workflow-status'

interface WorkflowStatusBadgeProps {
  status?: string | null
}

const toneClasses: Record<'default' | 'success' | 'warning' | 'danger', string> = {
  default: 'bg-muted text-muted-foreground',
  success: 'bg-emerald-100 text-emerald-800',
  warning: 'bg-amber-100 text-amber-800',
  danger: 'bg-red-100 text-red-800',
}

export function WorkflowStatusBadge({ status }: WorkflowStatusBadgeProps) {
  const tone = getWorkflowStatusTone(status)
  const label = getWorkflowStatusLabel(status)

  return (
    <span
      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize ${toneClasses[tone]}`}
      data-testid="workflow-status-badge"
      aria-label={`Status ${label}`}
    >
      {label}
    </span>
  )
}
