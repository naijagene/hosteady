import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import {
  normalizeDocumentReference,
  type DocumentReference,
} from '../types/documents'
import { asArray } from '../types/metadata-common'

export async function fetchDocuments(): Promise<DocumentReference[]> {
  const response = await apiClient.get<
    DocumentReference[] | { data: DocumentReference[] } | { data: unknown[] }
  >('tenant/documents')

  return asArray(unwrapData(response.data)).map(normalizeDocumentReference)
}
