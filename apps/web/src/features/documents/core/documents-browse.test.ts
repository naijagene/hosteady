import { describe, expect, it } from 'vitest'
import { formatDocumentSize, formatDocumentDate, getDocumentSubtitle } from '@/features/documents/core/document-normalizer'
import { resolveDocumentIcon } from '@/features/documents/core/document-icons'
import { createDocumentStatusFilter } from '@/features/documents/core/document-filters'
import { toggleDocumentSort } from '@/features/documents/core/document-sorts'
import { getDeleteConfirmationMessage, getDocumentActionPlaceholder } from '@/features/documents/core/document-actions'
import {
  canDownloadDocuments,
  canManageDocuments,
  hasDocumentPermission,
} from '@/features/documents/core/document-permissions'
import {
  getDocumentSelectionValue,
  isDocumentSelected,
  toggleDocumentSelection,
} from '@/features/documents/core/document-selection'
import { normalizeDocumentBindingContext, normalizeDocumentItem } from '@/api/types/documents'

describe('document icon matrix', () => {
  it.each([
    ['application/pdf', 'PDF'],
    ['image/png', 'IMG'],
    ['application/vnd.ms-excel', 'XLS'],
    ['application/msword', 'DOC'],
    ['application/zip', 'ZIP'],
    ['text/plain', 'TXT'],
    ['application/octet-stream', 'FILE'],
  ])('maps %s to %s', (mimeType, icon) => {
    expect(resolveDocumentIcon(mimeType)).toBe(icon)
  })
})

describe('document formatting helpers', () => {
  it.each([
    [0, '0 B'],
    [1024, '1 KB'],
    [1048576, '1.0 MB'],
    [null, '—'],
  ])('formats size %s as %s', (size, expected) => {
    expect(formatDocumentSize(size)).toBe(expected)
  })

  it('formats invalid dates safely', () => {
    expect(formatDocumentDate('not-a-date')).toBe('not-a-date')
  })

  it('returns subtitle fallbacks', () => {
    expect(getDocumentSubtitle({ public_id: 'x', title: 'Doc', category: 'general' })).toBe('general')
  })
})

describe('document binding and item normalization', () => {
  it.each([
    ['list', 'list'],
    ['grid', 'grid'],
    ['compact', 'compact'],
    ['picker', 'picker'],
    ['unknown', 'list'],
  ])('normalizes binding mode %s to %s', (mode, expected) => {
    expect(normalizeDocumentBindingContext({ mode }).mode).toBe(expected)
  })

  it('normalizes metadata owner and tags on items', () => {
    const item = normalizeDocumentItem({
      publicId: 'doc-1',
      title: 'Doc',
      metadata: { owner: 'Sam', tags: [{ label: 'Finance' }] },
    })

    expect(item.public_id).toBe('doc-1')
    expect(item.owner).toBe('Sam')
    expect(item.tags?.[0]?.label).toBe('Finance')
  })
})

describe('document permissions and actions', () => {
  it.each([
    ['documents.read', true],
    ['documents.upload', false],
  ])('permission %s read=%s', (permission, canRead) => {
    expect(hasDocumentPermission([permission], permission)).toBe(true)
    expect(canDownloadDocuments([permission])).toBe(canRead)
  })

  it('checks manage permission', () => {
    expect(canManageDocuments(['documents.manage'])).toBe(true)
  })

  it.each(['replace_version', 'attach_record', 'preview'])('returns placeholder for %s', (action) => {
    expect(getDocumentActionPlaceholder(action)).toContain('not')
  })

  it('builds delete confirmation message', () => {
    expect(getDeleteConfirmationMessage('Policy')).toContain('Policy')
  })
})

describe('document selection helpers', () => {
  const doc = { public_id: 'doc-1', title: 'Alpha' }

  it('toggles single selection', () => {
    expect(toggleDocumentSelection([], doc, false)).toHaveLength(1)
    expect(toggleDocumentSelection([doc], doc, false)).toHaveLength(0)
  })

  it('supports multiple selection', () => {
    const second = { public_id: 'doc-2', title: 'Beta' }
    const selected = toggleDocumentSelection([doc], second, true)
    expect(selected).toHaveLength(2)
  })

  it('returns selection value and selected state', () => {
    expect(getDocumentSelectionValue(doc)).toBe('doc-1')
    expect(isDocumentSelected([doc], 'doc-1')).toBe(true)
  })
})

describe('document filter and sort helpers', () => {
  it('creates status filter', () => {
    expect(createDocumentStatusFilter('active').value).toBe('active')
  })

  it('toggles sort from asc to desc to cleared', () => {
    expect(toggleDocumentSort([], 'title')).toEqual([{ sort_key: 'title', direction: 'asc' }])
    expect(toggleDocumentSort([{ sort_key: 'title', direction: 'asc' }], 'title')).toEqual([
      { sort_key: 'title', direction: 'desc' },
    ])
    expect(toggleDocumentSort([{ sort_key: 'title', direction: 'desc' }], 'title')).toEqual([])
  })
})
