import { useQuery } from '@tanstack/react-query'
import { fetchTableDefinition } from '@/api/endpoints/tables'
import { normalizeTableBindingContext } from '@/api/types/tables'
import type { UiComponent } from '@/api/types/ui'
import { DynamicTableRenderer } from '@/features/tables/components/DynamicTableRenderer'
import { bindingQueryEnabled } from '../core/binding-resolver'
import { useComponentBinding } from '../hooks/useComponentBinding'
import { useOptionalRendererContext } from '../hooks/useRendererContext'

interface TableBindingRendererProps {
  component: UiComponent
}

export function TableBindingRenderer({ component }: TableBindingRendererProps) {
  const binding = useComponentBinding(component)
  const rendererContext = useOptionalRendererContext()
  const definitionQuery = useQuery({
    queryKey: ['table-definition', binding?.moduleKey, binding?.resourceKey],
    queryFn: () =>
      fetchTableDefinition(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey),
  })

  const shouldQuery = bindingQueryEnabled(binding)

  if (!binding) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="table-binding-missing">
        Table binding unavailable
      </div>
    )
  }

  if (definitionQuery.isLoading) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="table-binding-loading">
        Loading table metadata…
      </div>
    )
  }

  if (definitionQuery.isError || !definitionQuery.data) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="table-binding-error">
        Unable to load table metadata.
      </div>
    )
  }

  const tableBinding = normalizeTableBindingContext(
    {
      ...binding.config,
      ...component.binding_config,
      page: rendererContext?.pageKey,
      binding: component.component_key,
      auto_query: shouldQuery,
      query_enabled: shouldQuery || binding.config.query_enabled === true,
    },
    binding.moduleKey,
    binding.resourceKey,
  )

  return (
    <div data-testid="table-binding-renderer">
      <DynamicTableRenderer
        definition={definitionQuery.data}
        binding={tableBinding}
        queryEnabled={shouldQuery || tableBinding.query_enabled !== false}
      />
    </div>
  )
}
