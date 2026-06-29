import { describe, expect, it } from 'vitest'
import {
  createInitialDocumentQuery,
  mergeDocumentQueryPayload,
  paginateDocuments,
  queryDocumentsLocally,
} from '@/features/documents/core/document-query'
import { applyDocumentFilters, createDocumentStatusFilter } from '@/features/documents/core/document-filters'
import { applyDocumentSorts, toggleDocumentSort } from '@/features/documents/core/document-sorts'
import {
  formatDocumentDate,
  formatDocumentSize,
  getDocumentDisplayTitle,
} from '@/features/documents/core/document-normalizer'
import {
  canDeleteDocuments,
  canReadDocuments,
  canUploadDocuments,
  hasDocumentPermission,
} from '@/features/documents/core/document-permissions'
import { resolveDocumentIcon } from '@/features/documents/core/document-icons'
import {
  isDocumentSelected,
  resolveDocumentReferenceFromValue,
  toggleDocumentSelection,
} from '@/features/documents/core/document-selection'
import { toDocumentQueryError } from '@/features/documents/core/document-errors'
import { ApiError } from '@/api/errors'
import type { DocumentItem } from '@/api/types/documents'

const documents: DocumentItem[] = [
  {
    public_id: 'doc-1',
    title: 'Alpha',
    status: 'active',
    mime_type: 'application/pdf',
    size_bytes: 2048,
    updated_at: '2024-02-01',
  },
  {
    public_id: 'doc-2',
    title: 'Beta',
    status: 'archived',
    mime_type: 'text/plain',
    size_bytes: 512,
    updated_at: '2024-01-01',
  },
]

describe('document query core', () => {
  it('creates initial query payload', () => {
    expect(createInitialDocumentQuery().page).toBe(1)
  })

  it('queries documents locally with search and pagination', () => {
    const result = queryDocumentsLocally(documents, {
      page: 1,
      per_page: 1,
      search: 'alpha',
      filters: [],
      sorts: [{ sort_key: 'title', direction: 'asc' }],
    })

    expect(result.items).toHaveLength(1)
    expect(result.total).toBe(1)
  })

  it('paginates documents', () => {
    const page = paginateDocuments(documents, 1, 1)
    expect(page.items).toHaveLength(1)
    expect(page.has_more).toBe(true)
  })

  it('merges query payload patches', () => {
    const merged = mergeDocumentQueryPayload(createInitialDocumentQuery(), { search: 'policy' })
    expect(merged.search).toBe('policy')
  })
})

describe('document filters and sorts', () => {
  it('filters by status', () => {
    const filtered = applyDocumentFilters(documents, [createDocumentStatusFilter('active')])
    expect(filtered).toHaveLength(1)
  })

  it('sorts by updated date descending', () => {
    const sorted = applyDocumentSorts(documents, [{ sort_key: 'updated_at', direction: 'desc' }])
    expect(sorted[0]?.public_id).toBe('doc-1')
  })

  it('toggles sort direction', () => {
    expect(toggleDocumentSort([], 'title')).toEqual([{ sort_key: 'title', direction: 'asc' }])
  })
})

describe('document display helpers', () => {
  it('formats size and date safely', () => {
    expect(formatDocumentSize(2048)).toBe('2 KB')
    expect(formatDocumentDate(null)).toBe('—')
  })

  it('resolves display title fallback', () => {
    expect(getDocumentDisplayTitle({ public_id: 'x', title: '', filename: 'file.txt' })).toBe('file.txt')
  })

  it('resolves icons by mime type', () => {
    expect(resolveDocumentIcon('application/pdf')).toBe('PDF')
    expect(resolveDocumentIcon('unknown/type')).toBe('FILE')
  })
})

describe('document permissions and selection', () => {
  it('checks permissions', () => {
    expect(hasDocumentPermission(['documents.read'], 'documents.read')).toBe(true)
    expect(canUploadDocuments(['documents.upload'])).toBe(true)
    expect(canDeleteDocuments(['documents.manage'])).toBe(true)
    expect(canReadDocuments([])).toBe(true)
  })

  it('toggles selection', () => {
    const selected = toggleDocumentSelection([], { public_id: 'doc-1', title: 'Alpha' })
    expect(isDocumentSelected(selected, 'doc-1')).toBe(true)
  })

  it('resolves reference from string value', () => {
    expect(resolveDocumentReferenceFromValue('doc-1')?.public_id).toBe('doc-1')
  })
})

describe('document errors', () => {
  it('normalizes ApiError', () => {
    expect(toDocumentQueryError(new ApiError('Failed', { status: 500 })).message).toBe('Failed')
  })
})
