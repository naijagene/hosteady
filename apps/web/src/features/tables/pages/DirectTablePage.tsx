import { useParams } from '@tanstack/react-router'
import { useQuery } from '@tanstack/react-query'
import { fetchTableDefinition } from '@/api/endpoints/tables'
import {
  DynamicTableRenderer,
  TableLoadingState,
} from '@/features/tables'

export function DirectTablePage() {
  const { moduleKey, tableKey } = useParams({ strict: false }) as {
    moduleKey: string
    tableKey: string
  }

  const query = useQuery({
    queryKey: ['table-definition', moduleKey, tableKey],
    queryFn: () => fetchTableDefinition(moduleKey, tableKey),
    enabled: Boolean(moduleKey && tableKey),
  })

  if (query.isLoading) {
    return <TableLoadingState />
  }

  if (query.isError || !query.data) {
    return (
      <div className="rounded-md border border-border bg-card p-4 text-sm text-muted-foreground">
        Unable to load table.
      </div>
    )
  }

  return (
    <div className="mx-auto w-full max-w-6xl">
      <DynamicTableRenderer
        definition={query.data}
        binding={{
          moduleKey,
          tableKey,
          source: 'web',
          page: `/tables/${moduleKey}/${tableKey}`,
          auto_query: true,
          query_enabled: true,
          refresh_on_form_success: true,
        }}
      />
    </div>
  )
}
