import type { UiComponent } from '@/api/types/ui'

interface WorkflowQueuePlaceholderProps {
  component: UiComponent
}

export function WorkflowQueuePlaceholder({
  component,
}: WorkflowQueuePlaceholderProps) {
  return (
    <div
      className="rounded-lg border border-border bg-card p-4"
      data-testid="workflow-queue-placeholder"
    >
      <h4 className="text-sm font-medium text-foreground">{component.name}</h4>
      <p className="mt-2 text-xs text-muted-foreground">
        Workflow queue binding placeholder
      </p>
    </div>
  )
}
