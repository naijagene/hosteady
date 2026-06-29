import { useQuery } from '@tanstack/react-query'
import { fetchFormDefinition } from '@/api/endpoints/forms'
import type { UiComponent } from '@/api/types/ui'
import { useComponentBinding } from '../hooks/useComponentBinding'

interface FormBindingRendererProps {
  component: UiComponent
}

export function FormBindingRenderer({ component }: FormBindingRendererProps) {
  const binding = useComponentBinding(component)
  const query = useQuery({
    queryKey: ['form-definition', binding?.moduleKey, binding?.resourceKey],
    queryFn: () =>
      fetchFormDefinition(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey),
  })

  if (!binding) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="form-binding-missing">
        Form binding unavailable
      </div>
    )
  }

  if (query.isLoading) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="form-binding-loading">
        Loading form metadata…
      </div>
    )
  }

  if (query.isError || !query.data) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="form-binding-error">
        Unable to load form metadata
      </div>
    )
  }

  const fields = query.data.fields ?? []

  return (
    <section
      className="rounded-lg border border-border bg-card p-4"
      data-testid="form-binding-renderer"
    >
      <header className="mb-3">
        <h4 className="text-sm font-medium text-foreground">{query.data.name}</h4>
        <p className="text-xs text-muted-foreground">Read-only form preview</p>
      </header>
      <dl className="space-y-2">
        {fields.length === 0 ? (
          <p className="text-xs text-muted-foreground">No fields defined</p>
        ) : (
          fields.map((field) => (
            <div key={field.field_key} className="grid gap-1">
              <dt className="text-xs font-medium text-foreground">{field.label}</dt>
              <dd className="text-xs text-muted-foreground">{field.field_type}</dd>
            </div>
          ))
        )}
      </dl>
      <button
        type="button"
        disabled
        className="mt-4 rounded-md border border-border px-3 py-1 text-xs text-muted-foreground"
      >
        Submit (placeholder)
      </button>
    </section>
  )
}
