import { ApiError } from '@/api/errors'
import type { TableQueryError } from '@/api/types/tables'

export function toTableQueryError(error: unknown): TableQueryError {
  if (error instanceof ApiError) {
    return {
      message: error.message,
      status: error.status,
      field_errors: error.fieldErrors,
    }
  }

  if (error instanceof Error) {
    return {
      message: error.message,
      status: null,
    }
  }

  return {
    message: 'Unable to load table data.',
    status: null,
  }
}
