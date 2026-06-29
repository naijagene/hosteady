import type { DocumentFilter, DocumentItem } from '@/api/types/documents'

function resolveDocumentFieldValue(document: DocumentItem, key: string): unknown {
  switch (key) {
    case 'status':
      return document.status
    case 'category':
      return document.category
    case 'mime_type':
      return document.mime_type
    case 'module_key':
      return document.module_key
    case 'visibility':
      return document.visibility
    default:
      return document.metadata?.[key]
  }
}

export function applyDocumentFilters(items: DocumentItem[], filters: DocumentFilter[]): DocumentItem[] {
  return filters.reduce<DocumentItem[]>((accumulator, filter) => {
    if (filter.value === undefined || filter.value === null || filter.value === '') {
      return accumulator
    }

    return accumulator.filter((document) => {
      const value = resolveDocumentFieldValue(document, filter.filter_key)
      const expected = filter.value

      switch (filter.operator ?? 'equals') {
        case 'contains':
          return String(value ?? '')
            .toLowerCase()
            .includes(String(expected).toLowerCase())
        default:
          return String(value ?? '') === String(expected)
      }
    })
  }, items)
}

export function createDocumentStatusFilter(status: string): DocumentFilter {
  return {
    filter_key: 'status',
    label: 'Status',
    filter_type: 'select',
    operator: 'equals',
    value: status,
  }
}

export function isSupportedDocumentFilterType(filterType: string): boolean {
  return ['text', 'select', 'date', 'boolean'].includes(filterType.toLowerCase())
}
