import type { DocumentItem, DocumentSort } from '@/api/types/documents'

function compareValues(left: unknown, right: unknown): number {
  if (left === right) {
    return 0
  }

  if (left === null || left === undefined) {
    return 1
  }

  if (right === null || right === undefined) {
    return -1
  }

  if (typeof left === 'number' && typeof right === 'number') {
    return left - right
  }

  return String(left).localeCompare(String(right))
}

function resolveSortValue(document: DocumentItem, sortKey: string): unknown {
  switch (sortKey) {
    case 'title':
      return document.title
    case 'updated_at':
      return document.updated_at
    case 'created_at':
      return document.created_at
    case 'size_bytes':
      return document.size_bytes
    case 'status':
      return document.status
    default:
      return document.metadata?.[sortKey]
  }
}

export function applyDocumentSorts(items: DocumentItem[], sorts: DocumentSort[]): DocumentItem[] {
  if (sorts.length === 0) {
    return items
  }

  return [...items].sort((left, right) => {
    for (const sort of sorts) {
      const comparison = compareValues(
        resolveSortValue(left, sort.sort_key),
        resolveSortValue(right, sort.sort_key),
      )

      if (comparison !== 0) {
        return sort.direction === 'desc' ? -comparison : comparison
      }
    }

    return 0
  })
}

export function toggleDocumentSort(
  current: DocumentSort[],
  sortKey: string,
): DocumentSort[] {
  const existing = current.find((sort) => sort.sort_key === sortKey)

  if (!existing) {
    return [{ sort_key: sortKey, direction: 'asc' }]
  }

  if (existing.direction === 'asc') {
    return [{ sort_key: sortKey, direction: 'desc' }]
  }

  return current.filter((sort) => sort.sort_key !== sortKey)
}
