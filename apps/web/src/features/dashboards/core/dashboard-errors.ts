import { ApiError } from '@/api/errors'

export interface DashboardQueryError {
  message: string
  status?: number | null
}

export function toDashboardQueryError(error: unknown): DashboardQueryError {
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
    message: 'Unable to load dashboard.',
    status: null,
  }
}
