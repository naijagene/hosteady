import { useMemo, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { fetchDocuments } from '@/api/endpoints/documents'
import type { DocumentBindingContext, DocumentQueryPayload } from '@/api/types/documents'
import { createInitialDocumentQuery, mergeDocumentQueryPayload, queryDocumentsLocally } from '../core/document-query'
import { toDocumentQueryError } from '../core/document-errors'

export function useDocumentsQuery(binding?: DocumentBindingContext) {
  const [queryPayload, setQueryPayload] = useState<DocumentQueryPayload>(() =>
    createInitialDocumentQuery({
      per_page: binding?.per_page ?? 25,
      filters: binding?.filters ?? [],
      sorts: binding?.sorts ?? [{ sort_key: 'updated_at', direction: 'desc' }],
      metadata: {
        source: binding?.source ?? 'web',
        page: binding?.page,
        binding: binding?.binding,
      },
    }),
  )

  const query = useQuery({
    queryKey: ['documents-query', queryPayload, binding?.mode],
    queryFn: () => fetchDocuments(queryPayload),
    enabled: binding?.query_enabled !== false,
  })

  const resolvedResult = useMemo(() => {
    if (!query.data) {
      return null
    }

    const local = queryDocumentsLocally(query.data.items, queryPayload)
    return {
      items: local.items,
      page: queryPayload.page ?? 1,
      per_page: queryPayload.per_page ?? 25,
      total: local.total,
      has_more: local.has_more,
    }
  }, [query.data, queryPayload])

  return {
    query,
    queryPayload,
    setQueryPayload,
    updateQueryPayload: (patch: Partial<DocumentQueryPayload>) =>
      setQueryPayload((current) => mergeDocumentQueryPayload(current, patch)),
    result: resolvedResult,
    error: query.error ? toDocumentQueryError(query.error) : null,
    refresh: () => query.refetch(),
  }
}
