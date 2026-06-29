import type { UseFormSetError } from 'react-hook-form'
import { ApiError } from '@/api/errors'
import type { FormSubmissionError } from '@/api/types/forms'

export function toFormSubmissionError(error: unknown): FormSubmissionError {
  if (error instanceof ApiError) {
    return {
      message: error.message,
      field_errors: error.fieldErrors,
      status: error.status,
    }
  }

  if (error instanceof Error) {
    return {
      message: error.message,
      field_errors: {},
      status: null,
    }
  }

  return {
    message: 'An unexpected error occurred.',
    field_errors: {},
    status: null,
  }
}

export function applyBackendFieldErrors(
  setError: UseFormSetError<Record<string, unknown>>,
  fieldErrors: Record<string, string[]>,
): void {
  Object.entries(fieldErrors).forEach(([field, messages]) => {
    setError(field, {
      type: 'server',
      message: messages[0] ?? 'Invalid value.',
    })
  })
}

export function flattenFieldErrors(
  fieldErrors: Record<string, string[]>,
): string[] {
  return Object.entries(fieldErrors).flatMap(([field, messages]) =>
    messages.map((message) => `${field}: ${message}`),
  )
}
