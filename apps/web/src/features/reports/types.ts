import type {
  ReportAction,
  ReportDefinition,
  ReportMetric,
  ReportParameter,
  ReportSection,
} from '@/api/types/reports'

export interface NormalizedReportModel {
  definition: ReportDefinition
  parameters: ReportParameter[]
  sections: ReportSection[]
  metrics: ReportMetric[]
  actions: ReportAction[]
}

export interface ReportParameterState {
  values: Record<string, unknown>
  applied: Record<string, unknown>
}
