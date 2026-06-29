import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import { asArray } from '../types/metadata-common'
import {
  buildDocumentQueryRequest,
  buildDocumentUploadFormData,
  normalizeDocumentAttachment,
  normalizeDocumentItem,
  normalizeDocumentQueryPayload,
  normalizeDocumentQueryResult,
  normalizeDocumentUploadResult,
  normalizeDocumentVersion,
  type DocumentAttachment,
  type DocumentItem,
  type DocumentQueryPayload,
  type DocumentQueryResult,
  type DocumentReference,
  type DocumentUploadPayload,
  type DocumentUploadResult,
  type DocumentVersion,
} from '../types/documents'

export async function fetchDocuments(payload: DocumentQueryPayload = {}): Promise<DocumentQueryResult> {
  try {
    const normalizedPayload = normalizeDocumentQueryPayload(payload)
    const response = await apiClient.get<
      DocumentReference[] | DocumentQueryResult | { data: unknown[] | DocumentQueryResult }
    >('tenant/documents', {
      params: buildDocumentQueryRequest(normalizedPayload),
    })

    const data = unwrapData(response.data)

    if (Array.isArray(data)) {
      const items = data.map(normalizeDocumentItem)
      return {
        items,
        page: normalizedPayload.page ?? 1,
        per_page: normalizedPayload.per_page ?? 25,
        total: items.length,
        has_more: false,
      }
    }

    return normalizeDocumentQueryResult(data, normalizedPayload)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchDocument(documentPublicId: string): Promise<DocumentItem> {
  try {
    const response = await apiClient.get<DocumentItem | { data: DocumentItem }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}`,
    )

    return normalizeDocumentItem(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function uploadDocument(payload: DocumentUploadPayload): Promise<DocumentUploadResult> {
  try {
    const response = await apiClient.post<DocumentUploadResult | { data: DocumentUploadResult }>(
      'tenant/documents',
      buildDocumentUploadFormData(payload),
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      },
    )

    return normalizeDocumentUploadResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function deleteDocument(documentPublicId: string): Promise<DocumentItem> {
  try {
    const response = await apiClient.delete<DocumentItem | { data: DocumentItem }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}`,
    )

    return normalizeDocumentItem(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchDocumentVersions(documentPublicId: string): Promise<DocumentVersion[]> {
  try {
    const response = await apiClient.get<DocumentVersion[] | { data: DocumentVersion[] }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}/versions`,
    )

    return asArray(unwrapData(response.data)).map(normalizeDocumentVersion)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function uploadDocumentVersion(
  documentPublicId: string,
  file: File,
): Promise<DocumentVersion> {
  try {
    const formData = new FormData()
    formData.append('file', file)

    const response = await apiClient.post<DocumentVersion | { data: DocumentVersion }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}/versions`,
      formData,
      {
        headers: { 'Content-Type': 'multipart/form-data' },
      },
    )

    return normalizeDocumentVersion(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchDocumentAttachments(
  documentPublicId: string,
): Promise<DocumentAttachment[]> {
  try {
    const response = await apiClient.get<DocumentAttachment[] | { data: DocumentAttachment[] }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}/attachments`,
    )

    return asArray(unwrapData(response.data)).map(normalizeDocumentAttachment)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function createDocumentAttachment(
  documentPublicId: string,
  payload: Record<string, unknown>,
): Promise<DocumentAttachment> {
  try {
    const response = await apiClient.post<DocumentAttachment | { data: DocumentAttachment }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}/attachments`,
      payload,
    )

    return normalizeDocumentAttachment(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchDocumentDownloadUrl(documentPublicId: string): Promise<string | null> {
  try {
    const response = await apiClient.get<{ url?: string; download_url?: string } | { data: { url?: string } }>(
      `tenant/documents/${encodeURIComponent(documentPublicId)}/download`,
    )
    const data = unwrapData(response.data) as Record<string, unknown>
    const url = data.url ?? data.download_url ?? data.downloadUrl
    return typeof url === 'string' ? url : null
  } catch {
    return null
  }
}
