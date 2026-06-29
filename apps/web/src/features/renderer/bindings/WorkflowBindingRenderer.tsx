import { useQuery } from '@tanstack/react-query'
import {
  fetchWorkflowDefinitions,
  fetchWorkflowInstances,
} from '@/api/endpoints/workflows'
import type { UiComponent } from '@/api/types/ui'

interface WorkflowBindingRendererProps {
  component: UiComponent
}

export function WorkflowBindingRenderer({ component }: WorkflowBindingRendererProps) {
  const definitionsQuery = useQuery({
    queryKey: ['workflow-definitions'],
    queryFn: fetchWorkflowDefinitions,
  })
  const instancesQuery = useQuery({
    queryKey: ['workflow-instances'],
    queryFn: fetchWorkflowInstances,
  })

  if (definitionsQuery.isLoading || instancesQuery.isLoading) {
    return (
      <div
        className="text-sm text-muted-foreground"
        data-testid="workflow-binding-loading"
      >
        Loading workflows…
      </div>
    )
  }

  const definitions = definitionsQuery.data ?? []
  const instances = instancesQuery.data ?? []

  return (
    <section
      className="rounded-lg border border-border bg-card p-4"
      data-testid="workflow-binding-renderer"
    >
      <h4 className="text-sm font-medium text-foreground">{component.name}</h4>
      <div className="mt-3 grid gap-4 md:grid-cols-2">
        <div>
          <p className="text-xs font-medium text-muted-foreground">Definitions</p>
          <ul className="mt-2 space-y-1">
            {definitions.length === 0 ? (
              <li className="text-xs text-muted-foreground">None</li>
            ) : (
              definitions.slice(0, 3).map((definition) => (
                <li key={definition.public_id} className="text-xs text-foreground">
                  {definition.name}
                </li>
              ))
            )}
          </ul>
        </div>
        <div>
          <p className="text-xs font-medium text-muted-foreground">Instances</p>
          <ul className="mt-2 space-y-1">
            {instances.length === 0 ? (
              <li className="text-xs text-muted-foreground">None</li>
            ) : (
              instances.slice(0, 3).map((instance) => (
                <li key={instance.public_id} className="text-xs text-foreground">
                  {instance.public_id}
                </li>
              ))
            )}
          </ul>
        </div>
      </div>
    </section>
  )
}
