import { useQuery } from '@tanstack/react-query'
import { fetchTableDefinition, queryTable } from '@/api/endpoints/tables'
import type { UiComponent } from '@/api/types/ui'
import { bindingQueryEnabled } from '../core/binding-resolver'
import { useComponentBinding } from '../hooks/useComponentBinding'

interface TableBindingRendererProps {
  component: UiComponent
}

export function TableBindingRenderer({ component }: TableBindingRendererProps) {
  const binding = useComponentBinding(component)
  const definitionQuery = useQuery({
    queryKey: ['table-definition', binding?.moduleKey, binding?.resourceKey],
    queryFn: () =>
      fetchTableDefinition(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey),
  })

  const shouldQuery = bindingQueryEnabled(binding)
  const rowsQuery = useQuery({
    queryKey: ['table-query', binding?.moduleKey, binding?.resourceKey],
    queryFn: () => queryTable(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey && shouldQuery),
  })

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

  const definition = definitionQuery.data
  const columns = definition?.columns ?? []
  const rows = rowsQuery.data?.rows ?? []

  return (
    <section
      className="overflow-hidden rounded-lg border border-border bg-card"
      data-testid="table-binding-renderer"
    >
      <header className="border-b border-border px-4 py-3">
        <h4 className="text-sm font-medium text-foreground">
          {definition?.name ?? component.name}
        </h4>
      </header>
      <div className="overflow-x-auto">
        <table className="min-w-full text-left text-xs">
          <thead className="bg-muted/40 text-muted-foreground">
            <tr>
              {columns.length === 0 ? (
                <th className="px-4 py-2">Column</th>
              ) : (
                columns.map((column) => (
                  <th key={column.column_key} className="px-4 py-2">
                    {column.label}
                  </th>
                ))
              )}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 ? (
              <tr>
                <td
                  className="px-4 py-3 text-muted-foreground"
                  colSpan={Math.max(columns.length, 1)}
                >
                  No rows available
                </td>
              </tr>
            ) : (
              rows.map((row, index) => (
                <tr key={index} className="border-t border-border">
                  {columns.map((column) => (
                    <td key={column.column_key} className="px-4 py-2 text-foreground">
                      {String(row[column.column_key] ?? '')}
                    </td>
                  ))}
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </section>
  )
}
