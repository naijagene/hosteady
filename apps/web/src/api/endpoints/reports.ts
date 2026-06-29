import { apiClient } from '../client'
import { unwrapData } from '../unwrap'
import {
  normalizeReportDefinition,
  normalizeReportRenderPayload,
  type ReportDefinition,
  type ReportRenderPayload,
} from '../types/reports'
import { asArray } from '../types/metadata-common'

export async function fetchReports(): Promise<ReportDefinition[]> {
  const response = await apiClient.get<
    ReportDefinition[] | { data: ReportDefinition[] } | { data: unknown[] }
  >('tenant/reports')

  return asArray(unwrapData(response.data)).map(normalizeReportDefinition)
}

export async function fetchReportDefinition(
  moduleKey: string,
  reportKey: string,
): Promise<ReportDefinition> {
  const response = await apiClient.get<
    ReportDefinition | { data: ReportDefinition }
  >(
    `tenant/reports/${encodeURIComponent(moduleKey)}/${encodeURIComponent(reportKey)}`,
  )

  return normalizeReportDefinition(unwrapData(response.data))
}

export async function fetchReportRender(
  moduleKey: string,
  reportKey: string,
): Promise<ReportRenderPayload> {
  const response = await apiClient.get<
    ReportRenderPayload | { data: ReportRenderPayload }
  >(
    `tenant/reports/${encodeURIComponent(moduleKey)}/${encodeURIComponent(reportKey)}/render`,
  )

  return normalizeReportRenderPayload(unwrapData(response.data))
}
