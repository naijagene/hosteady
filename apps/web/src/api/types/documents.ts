import { asRecord, asString, type MetadataRecord } from './metadata-common'

export interface DocumentReference {
  public_id: string
  title: string
  document_type?: string
  status?: string
  updated_at?: string | null
  metadata?: MetadataRecord
}

export function normalizeDocumentReference(raw: unknown): DocumentReference {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    title: asString(data.title, 'Document'),
    document_type: asString(data.document_type ?? data.documentType),
    status: asString(data.status),
    updated_at:
      typeof (data.updated_at ?? data.updatedAt) === 'string'
        ? ((data.updated_at ?? data.updatedAt) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}
