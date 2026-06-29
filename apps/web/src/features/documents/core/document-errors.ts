import { ApiError } from '@/api/errors'

export interface DocumentQueryError {
  message: string
  status?: number | null
}

export function toDocumentQueryError(error: unknown): DocumentQueryError {
  if (error instanceof ApiError) {
    return {
      message: error.message,
      status: error.status,
    }
  }

  if (error instanceof Error) {
    return {
      message: error.message,
      status: null,
    }
  }

  return {
    message: 'Unable to load documents.',
    status: null,
  }
}
