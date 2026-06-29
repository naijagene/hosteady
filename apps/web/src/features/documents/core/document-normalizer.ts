import type { DocumentItem, DocumentQueryPayload, DocumentQueryResult } from '@/api/types/documents'

export function normalizeDocumentBrowseResult(
  result: DocumentQueryResult,
  payload: DocumentQueryPayload,
): DocumentQueryResult {
  return {
    ...result,
    page: payload.page ?? result.page,
    per_page: payload.per_page ?? result.per_page,
  }
}

export function getDocumentDisplayTitle(document: DocumentItem): string {
  return document.title || document.filename || 'Document'
}

export function getDocumentSubtitle(document: DocumentItem): string {
  return document.mime_type || document.document_type || document.category || 'Unknown type'
}

export function formatDocumentSize(sizeBytes?: number | null): string {
  if (sizeBytes === null || sizeBytes === undefined) {
    return '—'
  }

  if (sizeBytes < 1024) {
    return `${sizeBytes} B`
  }

  if (sizeBytes < 1024 * 1024) {
    return `${Math.round(sizeBytes / 1024)} KB`
  }

  return `${(sizeBytes / (1024 * 1024)).toFixed(1)} MB`
}

export function formatDocumentDate(value?: string | null): string {
  if (!value) {
    return '—'
  }

  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString()
}
