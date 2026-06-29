import { describe, expect, it } from 'vitest'
import {
  buildDocumentQueryRequest,
  buildDocumentUploadFormData,
  normalizeDocumentAttachment,
  normalizeDocumentBindingContext,
  normalizeDocumentItem,
  normalizeDocumentQueryPayload,
  normalizeDocumentQueryResult,
  normalizeDocumentReference,
  normalizeDocumentVersion,
} from '@/api/types/documents'

describe('document API normalization', () => {
  it('normalizes document reference camelCase keys', () => {
    const reference = normalizeDocumentReference({
      publicId: 'doc-1',
      title: 'Policy',
      documentType: 'pdf',
      updatedAt: '2024-01-01',
    })

    expect(reference.public_id).toBe('doc-1')
    expect(reference.document_type).toBe('pdf')
  })

  it('normalizes document item metadata fields', () => {
    const item = normalizeDocumentItem({
      public_id: 'doc-1',
      title: 'Policy',
      mime_type: 'application/pdf',
      size_bytes: 2048,
      metadata: { owner: 'Alex', tags: [{ label: 'HR' }] },
      attachment_count: 2,
      current_version_number: 3,
    })

    expect(item.mime_type).toBe('application/pdf')
    expect(item.owner).toBe('Alex')
    expect(item.tags?.[0]?.label).toBe('HR')
  })

  it('normalizes query payload', () => {
    const payload = normalizeDocumentQueryPayload({
      page: 2,
      perPage: 10,
      search: 'policy',
      metadata: { source: 'web', page: '/documents' },
    })

    expect(payload.page).toBe(2)
    expect(payload.per_page).toBe(10)
    expect(payload.search).toBe('policy')
  })

  it('normalizes query result from array fallback', () => {
    const result = normalizeDocumentQueryResult(
      [{ public_id: 'doc-1', title: 'One' }, { public_id: 'doc-2', title: 'Two' }],
      { page: 1, per_page: 25 },
    )

    expect(result.items).toHaveLength(2)
    expect(result.total).toBe(2)
  })

  it('normalizes binding context flags', () => {
    const binding = normalizeDocumentBindingContext({
      mode: 'grid',
      searchEnabled: true,
      uploadEnabled: false,
      selectionEnabled: true,
      emptyStateMessage: 'No docs',
    })

    expect(binding.mode).toBe('grid')
    expect(binding.upload_enabled).toBe(false)
    expect(binding.selection_enabled).toBe(true)
  })

  it('builds query request with limit mapping', () => {
    expect(buildDocumentQueryRequest({ page: 1, per_page: 25, search: 'a' })).toEqual({
      page: 1,
      per_page: 25,
      limit: 25,
      search: 'a',
      filters: [],
      sorts: [],
      metadata: { source: 'web' },
    })
  })

  it('builds upload form data', () => {
    const file = new File(['hello'], 'hello.txt', { type: 'text/plain' })
    const formData = buildDocumentUploadFormData({ file, title: 'Hello', tags: ['a'] })
    expect(formData.get('title')).toBe('Hello')
    expect(formData.get('file')).toBe(file)
  })

  it('normalizes versions and attachments', () => {
    const version = normalizeDocumentVersion({
      publicId: 'ver-1',
      documentPublicId: 'doc-1',
      versionNumber: 2,
    })
    const attachment = normalizeDocumentAttachment({
      publicId: 'att-1',
      documentPublicId: 'doc-1',
      subjectType: 'record',
      subjectPublicId: 'rec-1',
    })

    expect(version.version_number).toBe(2)
    expect(attachment.subject_type).toBe('record')
  })
})
