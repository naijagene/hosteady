import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import type { TenantContextResponse } from '../types/runtime'

export async function fetchTenantContext(): Promise<TenantContextResponse> {
  const response = await apiClient.get<
    TenantContextResponse | { data: TenantContextResponse }
  >('tenant/context')

  return unwrapData(response.data)
}
