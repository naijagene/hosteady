import type { AxiosError, AxiosResponse } from 'axios'
import { ApiError } from '../errors'
import type { ApiErrorBody } from '../types/api'

export interface ResponseInterceptorHandlers {
  onUnauthorized?: (error: ApiError) => void
  onForbidden?: (error: ApiError) => void
}

export function attachResponseSuccessInterceptor(
  response: AxiosResponse,
): AxiosResponse {
  return response
}

export function attachResponseErrorInterceptor(
  handlers: ResponseInterceptorHandlers,
) {
  return (error: AxiosError) => {
    const apiError = ApiError.fromAxios(error as AxiosError<ApiErrorBody>)

    if (apiError.kind === 'unauthorized') {
      handlers.onUnauthorized?.(apiError)
    }

    if (apiError.kind === 'forbidden') {
      handlers.onForbidden?.(apiError)
    }

    return Promise.reject(apiError)
  }
}
