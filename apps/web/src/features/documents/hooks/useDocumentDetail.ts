import { useQuery } from '@tanstack/react-query'
import {
  fetchDocument,
  fetchDocumentAttachments,
  fetchDocumentDownloadUrl,
  fetchDocumentVersions,
} from '@/api/endpoints/documents'
import { toDocumentQueryError } from '../core/document-errors'

export function useDocumentDetail(documentPublicId?: string | null, enabled = true) {
  const detailQuery = useQuery({
    queryKey: ['document-detail', documentPublicId],
    queryFn: () => fetchDocument(documentPublicId!),
    enabled: Boolean(documentPublicId && enabled),
  })

  const versionsQuery = useQuery({
    queryKey: ['document-versions', documentPublicId],
    queryFn: () => fetchDocumentVersions(documentPublicId!),
    enabled: Boolean(documentPublicId && enabled),
  })

  const attachmentsQuery = useQuery({
    queryKey: ['document-attachments', documentPublicId],
    queryFn: () => fetchDocumentAttachments(documentPublicId!),
    enabled: Boolean(documentPublicId && enabled),
  })

  const downloadQuery = useQuery({
    queryKey: ['document-download', documentPublicId],
    queryFn: () => fetchDocumentDownloadUrl(documentPublicId!),
    enabled: Boolean(documentPublicId && enabled),
  })

  return {
    document: detailQuery.data,
    versions: versionsQuery.data ?? [],
    attachments: attachmentsQuery.data ?? [],
    downloadUrl: downloadQuery.data,
    isLoading: detailQuery.isLoading,
    error: detailQuery.error
      ? toDocumentQueryError(detailQuery.error)
      : versionsQuery.error
        ? toDocumentQueryError(versionsQuery.error)
        : attachmentsQuery.error
          ? toDocumentQueryError(attachmentsQuery.error)
          : null,
    refresh: () => {
      detailQuery.refetch()
      versionsQuery.refetch()
      attachmentsQuery.refetch()
      downloadQuery.refetch()
    },
  }
}
