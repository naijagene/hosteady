import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import {
  buildReportExportRequest,
  normalizeReportDefinition,
  normalizeReportExportResult,
  normalizeReportRenderPayload,
  normalizeReportRunResult,
  type ReportDefinition,
  type ReportExportPayload,
  type ReportExportResult,
  type ReportRenderPayload,
  type ReportRunPayload,
  type ReportRunResult,
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
  try {
    const response = await apiClient.get<
      ReportRenderPayload | { data: ReportRenderPayload }
    >(
      `tenant/reports/${encodeURIComponent(moduleKey)}/${encodeURIComponent(reportKey)}/render`,
    )

    return normalizeReportRenderPayload(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function runReport(
  moduleKey: string,
  reportKey: string,
  payload: ReportRunPayload = {},
): Promise<ReportRunResult> {
  try {
    const response = await apiClient.post<
      ReportRunResult | { data: ReportRunResult }
    >(
      `tenant/reports/${encodeURIComponent(moduleKey)}/${encodeURIComponent(reportKey)}/run`,
      payload,
    )

    return normalizeReportRunResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function exportReport(
  moduleKey: string,
  reportKey: string,
  payload: ReportExportPayload,
): Promise<ReportExportResult> {
  try {
    const response = await apiClient.post<
      ReportExportResult | { data: ReportExportResult }
    >(
      `tenant/reports/${encodeURIComponent(moduleKey)}/${encodeURIComponent(reportKey)}/export`,
      buildReportExportRequest(payload),
    )

    return normalizeReportExportResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}
