import { useQuery, useQueryClient } from '@tanstack/react-query'
import { queryTable } from '@/api/endpoints/tables'
import type { TableBindingContext } from '@/api/types/tables'
import { toTableQueryError } from '../core/table-errors'
import {
  buildTableQueryPayload,
  tableQueryKey,
} from '../core/table-query'
import type { TableQueryState } from '../types'

export function useTableQuery(options: {
  moduleKey: string
  tableKey: string
  queryState: TableQueryState
  visibleColumnKeys: string[]
  binding?: TableBindingContext
  enabled?: boolean
}) {
  const queryClient = useQueryClient()
  const payload = buildTableQueryPayload(options.queryState, {
    binding: options.binding,
    visibleColumnKeys: options.visibleColumnKeys,
  })

  const query = useQuery({
    queryKey: tableQueryKey(
      options.moduleKey,
      options.tableKey,
      options.queryState,
      options.visibleColumnKeys,
    ),
    queryFn: () => queryTable(options.moduleKey, options.tableKey, payload),
    enabled:
      options.enabled !== false &&
      Boolean(options.moduleKey && options.tableKey) &&
      (options.binding?.query_enabled !== false),
  })

  return {
    ...query,
    errorInfo: query.error ? toTableQueryError(query.error) : null,
    refresh: () => query.refetch(),
    invalidate: () =>
      queryClient.invalidateQueries({
        queryKey: ['table-query', options.moduleKey, options.tableKey],
      }),
  }
}
