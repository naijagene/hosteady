import type { DocumentItem, DocumentQueryPayload } from '@/api/types/documents'
import { applyDocumentFilters } from './document-filters'
import { applyDocumentSorts } from './document-sorts'

export function createInitialDocumentQuery(overrides?: Partial<DocumentQueryPayload>): DocumentQueryPayload {
  return {
    page: 1,
    per_page: 25,
    search: '',
    filters: [],
    sorts: [{ sort_key: 'updated_at', direction: 'desc' }],
    metadata: { source: 'web' },
    ...overrides,
  }
}

export function paginateDocuments(items: DocumentItem[], page: number, perPage: number) {
  const start = Math.max(page - 1, 0) * perPage
  return {
    items: items.slice(start, start + perPage),
    total: items.length,
    has_more: start + perPage < items.length,
  }
}

export function queryDocumentsLocally(
  items: DocumentItem[],
  payload: DocumentQueryPayload,
): { items: DocumentItem[]; total: number; has_more: boolean } {
  const search = payload.search?.trim().toLowerCase() ?? ''
  let filtered = items

  if (search) {
    filtered = filtered.filter((document) => {
      const haystack = [
        document.title,
        document.filename,
        document.description,
        document.status,
        document.mime_type,
        ...(document.tags ?? []).map((tag) => tag.label),
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()

      return haystack.includes(search)
    })
  }

  filtered = applyDocumentFilters(filtered, payload.filters ?? [])
  filtered = applyDocumentSorts(filtered, payload.sorts ?? [])

  const page = payload.page ?? 1
  const perPage = payload.per_page ?? 25
  const paginated = paginateDocuments(filtered, page, perPage)

  return {
    items: paginated.items,
    total: paginated.total,
    has_more: paginated.has_more,
  }
}

export function mergeDocumentQueryPayload(
  current: DocumentQueryPayload,
  patch: Partial<DocumentQueryPayload>,
): DocumentQueryPayload {
  return {
    ...current,
    ...patch,
    filters: patch.filters ?? current.filters,
    sorts: patch.sorts ?? current.sorts,
    metadata: {
      ...current.metadata,
      ...patch.metadata,
    },
  }
}
