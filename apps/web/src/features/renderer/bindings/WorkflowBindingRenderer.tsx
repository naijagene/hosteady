import type { UiComponent } from '@/api/types/ui'
import { normalizeWorkflowBindingContext } from '@/api/types/workflows'
import { WorkflowInbox } from '@/features/workflows/components/WorkflowInbox'
import { WorkflowInstanceList } from '@/features/workflows/components/WorkflowInstanceList'
import { useWorkflowInstances } from '@/features/workflows/hooks/useWorkflowInstances'

interface WorkflowBindingRendererProps {
  component: UiComponent
}

export function WorkflowBindingRenderer({ component }: WorkflowBindingRendererProps) {
  const binding = normalizeWorkflowBindingContext({
    ...component.binding_config,
  })

  if (binding.mode === 'instances' || binding.mode === 'definitions') {
    return (
      <div data-testid="workflow-binding-renderer">
        <WorkflowInstanceBinding binding={binding} title={component.name} />
      </div>
    )
  }

  if (binding.mode === 'approvals') {
    return (
      <div data-testid="workflow-binding-renderer">
        <WorkflowInbox
          title={component.name}
          binding={{
            ...binding,
            mode: 'approvals',
          }}
        />
      </div>
    )
  }

  return (
    <div data-testid="workflow-binding-renderer">
      <WorkflowInbox title={component.name} binding={binding} />
    </div>
  )
}

function WorkflowInstanceBinding({
  binding,
  title,
}: {
  binding: ReturnType<typeof normalizeWorkflowBindingContext>
  title: string
}) {
  const { instances, query, error } = useWorkflowInstances({
    per_page: binding.per_page,
    status: binding.status_filter || undefined,
  })

  if (query.isLoading) {
    return (
      <section className="rounded-lg border border-border bg-card p-4" data-testid="workflow-binding-loading">
        Loading workflow instances…
      </section>
    )
  }

  if (error) {
    return (
      <section className="rounded-lg border border-border bg-card p-4" data-testid="workflow-binding-error">
        {error.message}
      </section>
    )
  }

  return (
    <section className="rounded-lg border border-border bg-card p-4">
      <h4 className="text-sm font-medium text-foreground">{title}</h4>
      <div className="mt-3">
        <WorkflowInstanceList instances={instances} emptyMessage={binding.empty_state_message} />
      </div>
    </section>
  )
}
