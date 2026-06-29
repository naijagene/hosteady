import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import {
  normalizeDashboardDefinition,
  normalizeDashboardRenderPayload,
  type DashboardDefinition,
  type DashboardRenderPayload,
} from '../types/dashboards'
import { asArray } from '../types/metadata-common'

export async function fetchDashboards(): Promise<DashboardDefinition[]> {
  const response = await apiClient.get<
    DashboardDefinition[] | { data: DashboardDefinition[] } | { data: unknown[] }
  >('tenant/dashboards')

  return asArray(unwrapData(response.data)).map(normalizeDashboardDefinition)
}

export async function fetchDashboardDefinition(
  moduleKey: string,
  dashboardKey: string,
): Promise<DashboardDefinition> {
  const response = await apiClient.get<
    DashboardDefinition | { data: DashboardDefinition }
  >(
    `tenant/dashboards/${encodeURIComponent(moduleKey)}/${encodeURIComponent(dashboardKey)}`,
  )

  return normalizeDashboardDefinition(unwrapData(response.data))
}

export async function fetchDashboardRender(
  moduleKey: string,
  dashboardKey: string,
): Promise<DashboardRenderPayload> {
  try {
    const response = await apiClient.get<
      DashboardRenderPayload | { data: DashboardRenderPayload }
    >(
      `tenant/dashboards/${encodeURIComponent(moduleKey)}/${encodeURIComponent(dashboardKey)}/render`,
    )

    return normalizeDashboardRenderPayload(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}
