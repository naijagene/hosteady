import type { InternalAxiosRequestConfig } from 'axios'
import { buildTenantHeaders } from '../tenant-headers'
import { useAuthStore } from '@/stores/auth-store'

export function attachRequestInterceptors(
  onUnauthorized?: () => void,
): (config: InternalAxiosRequestConfig) => InternalAxiosRequestConfig {
  return (config) => {
    const auth = useAuthStore.getState()

    if (auth.isSessionExpired()) {
      onUnauthorized?.()
      throw new Error('Session expired')
    }

    if (auth.accessToken) {
      config.headers.Authorization = `Bearer ${auth.accessToken}`
    }

    Object.assign(
      config.headers,
      buildTenantHeaders({
        organizationPublicId: auth.organization?.public_id ?? null,
        workspacePublicId: auth.workspace?.public_id ?? null,
        applicationPublicId: auth.application?.public_id ?? null,
      }),
    )

    return config
  }
}
