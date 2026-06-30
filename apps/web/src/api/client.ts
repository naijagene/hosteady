import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'
import { getApiBaseUrl } from '@/lib/env'
import { resetSession } from '@/features/auth/core/session-reset'
import {
  attachRequestInterceptors,
  attachResponseErrorInterceptor,
  attachResponseSuccessInterceptor,
} from './interceptors'

let unauthorizedHandler: (() => void) | undefined
let forbiddenHandler: (() => void) | undefined

export function configureApiClientHandlers(handlers: {
  onUnauthorized?: () => void
  onForbidden?: () => void
}): void {
  unauthorizedHandler = handlers.onUnauthorized
  forbiddenHandler = handlers.onForbidden
}

export function createApiClient(config?: AxiosRequestConfig): AxiosInstance {
  const client = axios.create({
    baseURL: getApiBaseUrl(),
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    ...config,
  })

  client.interceptors.request.use(
    attachRequestInterceptors(() => unauthorizedHandler?.()),
  )
  client.interceptors.response.use(
    attachResponseSuccessInterceptor,
    attachResponseErrorInterceptor({
      onUnauthorized: () => unauthorizedHandler?.(),
      onForbidden: () => forbiddenHandler?.(),
    }),
  )

  return client
}

export const apiClient = createApiClient()

export function cancelTokenSource() {
  return axios.CancelToken.source()
}

export function isRequestCancelled(error: unknown): boolean {
  return axios.isCancel(error)
}

export function resetAuthFromStore(): void {
  void resetSession()
}
