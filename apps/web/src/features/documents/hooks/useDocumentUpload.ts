import { useCallback, useState } from 'react'
import { useQueryClient } from '@tanstack/react-query'
import { uploadDocument } from '@/api/endpoints/documents'
import type { DocumentUploadPayload, DocumentUploadResult } from '@/api/types/documents'
import { toDocumentQueryError } from '../core/document-errors'

export function useDocumentUpload(options?: { enabled?: boolean }) {
  const queryClient = useQueryClient()
  const [isUploading, setIsUploading] = useState(false)
  const [result, setResult] = useState<DocumentUploadResult | null>(null)
  const [error, setError] = useState<string | null>(null)

  const upload = useCallback(
    async (payload: DocumentUploadPayload) => {
      if (options?.enabled === false) {
        setError('Upload is not enabled.')
        return null
      }

      setIsUploading(true)
      setError(null)

      try {
        const uploadResult = await uploadDocument(payload)
        setResult(uploadResult)
        await queryClient.invalidateQueries({ queryKey: ['documents-query'] })
        return uploadResult
      } catch (caught) {
        const normalized = toDocumentQueryError(caught)
        setError(normalized.message)
        setResult(null)
        return null
      } finally {
        setIsUploading(false)
      }
    },
    [options?.enabled, queryClient],
  )

  return {
    upload,
    isUploading,
    result,
    error,
    clearUploadState: () => {
      setResult(null)
      setError(null)
    },
  }
}
