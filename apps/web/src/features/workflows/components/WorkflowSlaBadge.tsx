interface WorkflowSlaBadgeProps {
  label: string
  overdue?: boolean
}

export function WorkflowSlaBadge({ label, overdue = false }: WorkflowSlaBadgeProps) {
  return (
    <span
      className={`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${
        overdue ? 'bg-red-100 text-red-800' : 'bg-sky-100 text-sky-800'
      }`}
      data-testid="workflow-sla-badge"
      aria-label={label}
    >
      {label}
    </span>
  )
}
