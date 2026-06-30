import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import { asRecord } from '../types/metadata-common'
import { normalizeAdminPlatformHealth, type AdminPlatformHealth } from '../types/admin'

export async function fetchWorkspaceRuntimeHealth(): Promise<AdminPlatformHealth | null> {
  try {
    const response = await apiClient.get('tenant/workspace/runtime/health')
    return normalizeAdminPlatformHealth(unwrapData(response.data), 'backend')
  } catch {
    return null
  }
}

export async function fetchTenantApplications(): Promise<unknown[]> {
  try {
    const response = await apiClient.get('tenant/applications')
    const data = unwrapData(response.data)
    return Array.isArray(data) ? data : Array.isArray(asRecord(data).data) ? (asRecord(data).data as unknown[]) : []
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function safeFetchTenantApplications(): Promise<unknown[]> {
  try {
    return await fetchTenantApplications()
  } catch {
    return []
  }
}

export async function pingApiEndpoint(path: string): Promise<boolean> {
  try {
    await apiClient.get(path)
    return true
  } catch {
    return false
  }
}
