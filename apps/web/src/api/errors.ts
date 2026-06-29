import type { AxiosError } from 'axios'
import type { ApiErrorBody } from './types/api'

export type ApiErrorKind =
  | 'network'
  | 'unauthorized'
  | 'forbidden'
  | 'not_found'
  | 'validation'
  | 'server'
  | 'unknown'

export class ApiError extends Error {
  readonly kind: ApiErrorKind
  readonly status: number | null
  readonly body: ApiErrorBody | null
  readonly fieldErrors: Record<string, string[]>

  constructor(
    message: string,
    options: {
      kind?: ApiErrorKind
      status?: number | null
      body?: ApiErrorBody | null
      cause?: unknown
    } = {},
  ) {
    super(message, { cause: options.cause })
    this.name = 'ApiError'
    this.kind = options.kind ?? 'unknown'
    this.status = options.status ?? null
    this.body = options.body ?? null
    this.fieldErrors = options.body?.errors ?? {}
  }

  static fromAxios(error: AxiosError<ApiErrorBody>): ApiError {
    const status = error.response?.status ?? null
    const body = error.response?.data ?? null
    const message =
      body?.message ??
      error.message ??
      'An unexpected API error occurred.'

    if (status === 401) {
      return new ApiError(message, { kind: 'unauthorized', status, body, cause: error })
    }

    if (status === 403) {
      return new ApiError(message, { kind: 'forbidden', status, body, cause: error })
    }

    if (status === 404) {
      return new ApiError(message, { kind: 'not_found', status, body, cause: error })
    }

    if (status === 422) {
      return new ApiError(message, { kind: 'validation', status, body, cause: error })
    }

    if (status !== null && status >= 500) {
      return new ApiError(message, { kind: 'server', status, body, cause: error })
    }

    if (!error.response) {
      return new ApiError(message, { kind: 'network', status, body, cause: error })
    }

    return new ApiError(message, { kind: 'unknown', status, body, cause: error })
  }
}
