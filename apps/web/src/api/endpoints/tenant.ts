import { apiClient } from '../client'
import type { TenantContextResponse } from '../types/runtime'

export async function fetchTenantContext(): Promise<TenantContextResponse> {
  const response = await apiClient.get<{ data: TenantContextResponse } | TenantContextResponse>(
    'tenant/context',
  )
  const payload = response.data

  if ('data' in payload && payload.data) {
    return payload.data
  }

  return payload as TenantContextResponse
}
