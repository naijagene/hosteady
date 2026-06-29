import { useQuery } from '@tanstack/react-query'
import { fetchFormDefinition } from '@/api/endpoints/forms'
import { normalizeFormBindingContext } from '@/api/types/forms'
import type { UiComponent } from '@/api/types/ui'
import { DynamicFormRenderer, FormLoadingState } from '@/features/forms'
import { useComponentBinding } from '../hooks/useComponentBinding'
import { useOptionalRendererContext } from '../hooks/useRendererContext'

interface FormBindingRendererProps {
  component: UiComponent
}

export function FormBindingRenderer({ component }: FormBindingRendererProps) {
  const binding = useComponentBinding(component)
  const rendererContext = useOptionalRendererContext()
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
    return <FormLoadingState />
  }

  if (query.isError || !query.data) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="form-binding-error">
        Unable to load form metadata
      </div>
    )
  }

  const bindingContext = normalizeFormBindingContext(
    {
      ...binding.config,
      ...component.binding_config,
      page: rendererContext?.pageKey,
      binding: component.component_key,
    },
    binding.moduleKey,
    binding.resourceKey,
  )

  return (
    <section
      className="rounded-lg border border-border bg-card p-4"
      data-testid="form-binding-renderer"
    >
      <DynamicFormRenderer definition={query.data} binding={bindingContext} />
    </section>
  )
}
