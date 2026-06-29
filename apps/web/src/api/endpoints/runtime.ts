import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import type {
  NavigationMenuResponse,
  PersonalizationRuntimeResponse,
  ThemeRuntimeResponse,
  WorkspaceRuntimeResponse,
} from '../types/runtime'

export async function fetchWorkspaceRuntime(): Promise<WorkspaceRuntimeResponse> {
  const response = await apiClient.get<
    WorkspaceRuntimeResponse | { data: WorkspaceRuntimeResponse }
  >('tenant/workspace/runtime')

  return unwrapData(response.data)
}

export async function fetchPersonalizationRuntime(): Promise<PersonalizationRuntimeResponse> {
  const response = await apiClient.get<
    PersonalizationRuntimeResponse | { data: PersonalizationRuntimeResponse }
  >('tenant/personalization/runtime')

  return unwrapData(response.data)
}

export async function fetchThemeRuntime(): Promise<ThemeRuntimeResponse> {
  const response = await apiClient.get<
    ThemeRuntimeResponse | { data: ThemeRuntimeResponse }
  >('tenant/themes/runtime')

  return unwrapData(response.data)
}

export async function fetchApplicationNavigation(): Promise<NavigationMenuResponse[]> {
  const response = await apiClient.get<
    NavigationMenuResponse[] | { data: NavigationMenuResponse[] }
  >('tenant/application-runtime/navigation')

  return unwrapData(response.data)
}
