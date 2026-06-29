import { useMemo } from 'react'
import { useQuery } from '@tanstack/react-query'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { buildCommandSearchResults, buildRuntimeSearchResults, runUniversalFinder } from '../core/universal-finder'
import { filterResultsByPermission } from '../core/search-permissions'
import { rankSearchResults } from '../core/search-ranking'
import { toSearchQueryError } from '../core/search-errors'
import { shouldSearch } from '../core/search-query'
import type { UniversalFinderContext } from '@/api/types/search'

export function useUniversalFinder(query: string, context?: UniversalFinderContext) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(() => runtime?.permissions ?? [], [runtime?.permissions])

  const queryResult = useQuery({
    queryKey: ['universal-finder', query, context, permissions],
    queryFn: () =>
      runUniversalFinder({
        query,
        runtime,
        permissions,
        context,
      }),
    enabled: shouldSearch(query) || context?.include_runtime !== false,
  })

  const defaultItems = useMemo(() => {
    const local = filterResultsByPermission(buildRuntimeSearchResults(runtime), permissions)
    const commands = buildCommandSearchResults('', permissions)
    return rankSearchResults([...local, ...commands], '').slice(0, context?.limit ?? 12)
  }, [context?.limit, permissions, runtime])

  return {
    query: queryResult,
    results: queryResult.data?.items ?? [],
    defaultItems,
    source: queryResult.data?.source ?? 'runtime',
    error: queryResult.error ? toSearchQueryError(queryResult.error) : null,
    isLoading: queryResult.isLoading,
    refresh: () => queryResult.refetch(),
  }
}
