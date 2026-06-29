import type { AxiosError, InternalAxiosRequestConfig } from 'axios'

const RETRY_HEADER = 'x-heos-retry'

export interface RetryOptions {
  maxRetries?: number
  shouldRetry?: (error: AxiosError) => boolean
}

export function attachRetryInterceptor(options: RetryOptions = {}) {
  const maxRetries = options.maxRetries ?? 1

  return async (error: AxiosError) => {
    const config = error.config as InternalAxiosRequestConfig & {
      __retryCount?: number
    }

    if (!config) {
      return Promise.reject(error)
    }

    const retryCount = config.__retryCount ?? 0
    const shouldRetry =
      options.shouldRetry?.(error) ??
      (!error.response || error.response.status >= 500)

    if (!shouldRetry || retryCount >= maxRetries) {
      return Promise.reject(error)
    }

    config.__retryCount = retryCount + 1
    config.headers.set(RETRY_HEADER, String(config.__retryCount))

    return Promise.resolve(config)
  }
}
