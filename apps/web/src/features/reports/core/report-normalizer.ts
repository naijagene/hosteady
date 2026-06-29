import type { ReportRenderPayload } from '@/api/types/reports'
import type { NormalizedReportModel } from '../types'
import { resolveReportSections } from './report-sections'

export function normalizeReportRenderModel(payload: ReportRenderPayload): NormalizedReportModel {
  return {
    definition: payload.report,
    parameters: payload.parameters ?? payload.report.parameters ?? [],
    sections: resolveReportSections(payload),
    metrics: payload.metrics ?? [],
    actions: payload.actions ?? payload.report.actions ?? [],
  }
}

export function getReportTitle(payload: ReportRenderPayload): string {
  return payload.report.name || 'Report'
}

export function getReportDescription(payload: ReportRenderPayload): string | null {
  return payload.report.description ?? null
}
