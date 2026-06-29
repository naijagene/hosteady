import type { DocumentReference } from '@/api/types/documents'

export function toggleDocumentSelection(
  selected: DocumentReference[],
  document: DocumentReference,
  multiple = false,
): DocumentReference[] {
  const exists = selected.some((item) => item.public_id === document.public_id)

  if (exists) {
    return selected.filter((item) => item.public_id !== document.public_id)
  }

  if (multiple) {
    return [...selected, document]
  }

  return [document]
}

export function isDocumentSelected(selected: DocumentReference[], publicId: string): boolean {
  return selected.some((item) => item.public_id === publicId)
}

export function getDocumentSelectionValue(document: DocumentReference): string {
  return document.public_id
}

export function resolveDocumentReferenceFromValue(value: unknown): DocumentReference | null {
  if (typeof value === 'string' && value.trim()) {
    return {
      public_id: value.trim(),
      title: value.trim(),
    }
  }

  if (value && typeof value === 'object') {
    const data = value as Record<string, unknown>
    const publicId = data.public_id ?? data.publicId

    if (typeof publicId === 'string' && publicId) {
      return {
        public_id: publicId,
        title: String(data.title ?? data.name ?? publicId),
        document_type: typeof data.document_type === 'string' ? data.document_type : undefined,
        status: typeof data.status === 'string' ? data.status : undefined,
        updated_at: typeof data.updated_at === 'string' ? data.updated_at : null,
        metadata: typeof data.metadata === 'object' ? (data.metadata as Record<string, unknown>) : undefined,
      }
    }
  }

  return null
}
