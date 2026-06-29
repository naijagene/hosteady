import { apiClient } from '../client'
import type {
  PersonalizationRuntimeResponse,
  WorkspaceRuntimeResponse,
} from '../types/runtime'

export async function fetchWorkspaceRuntime(): Promise<WorkspaceRuntimeResponse> {
  const response = await apiClient.get<
    { data: WorkspaceRuntimeResponse } | WorkspaceRuntimeResponse
  >('tenant/workspace/runtime')
  const payload = response.data

  if ('data' in payload && payload.data) {
    return payload.data
  }

  return payload as WorkspaceRuntimeResponse
}

export async function fetchPersonalizationRuntime(): Promise<PersonalizationRuntimeResponse> {
  const response = await apiClient.get<
    { data: PersonalizationRuntimeResponse } | PersonalizationRuntimeResponse
  >('tenant/personalization/runtime')
  const payload = response.data

  if ('data' in payload && payload.data) {
    return payload.data
  }

  return payload as PersonalizationRuntimeResponse
}
