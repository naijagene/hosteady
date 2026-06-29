import axios, { type AxiosInstance, type AxiosRequestConfig } from 'axios'
import { getApiBaseUrl } from '@/lib/env'
import { buildTenantHeaders } from './tenant-headers'
import { useAuthStore } from '@/stores/auth-store'
import { useSessionStore } from '@/stores/session-store'

export function createApiClient(config?: AxiosRequestConfig): AxiosInstance {
  const client = axios.create({
    baseURL: getApiBaseUrl(),
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    ...config,
  })

  client.interceptors.request.use((request) => {
    const token = useAuthStore.getState().token

    if (token) {
      request.headers.Authorization = `Bearer ${token}`
    }

    const session = useSessionStore.getState()

    Object.assign(
      request.headers,
      buildTenantHeaders({
        organizationPublicId: session.organizationPublicId,
        workspacePublicId: session.workspacePublicId,
        applicationPublicId: session.applicationPublicId,
      }),
    )

    return request
  })

  return client
}

export const apiClient = createApiClient()
