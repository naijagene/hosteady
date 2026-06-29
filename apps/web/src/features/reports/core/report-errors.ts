import { ApiError } from '@/api/errors'

export interface ReportQueryError {
  message: string
  status?: number | null
}

export function toReportQueryError(error: unknown): ReportQueryError {
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
    message: 'Unable to load report.',
    status: null,
  }
}
