import { ApiError } from '@/api/errors'

export function toSearchQueryError(error: unknown): { message: string; status?: number | null } {
  if (error instanceof ApiError) {
    return { message: error.message, status: error.status }
  }

  if (error instanceof Error) {
    return { message: error.message, status: null }
  }

  return { message: 'Unable to search right now.', status: null }
}
