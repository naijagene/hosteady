import { describe, expect, it } from 'vitest'
import {
  buildReportExportRequest,
  normalizeReportBindingContext,
  normalizeReportDefinition,
  normalizeReportExportResult,
  normalizeReportRenderPayload,
  normalizeReportRunResult,
  normalizeReportSection,
} from '@/api/types/reports'

describe('report API normalization', () => {
  it('normalizes report definition camelCase keys', () => {
    const definition = normalizeReportDefinition({
      moduleKey: 'platform',
      reportKey: 'summary',
      name: 'Summary Report',
      parameters: [{ parameterKey: 'status', label: 'Status', parameterType: 'select' }],
    })

    expect(definition.module_key).toBe('platform')
    expect(definition.report_key).toBe('summary')
    expect(definition.parameters?.[0]?.parameter_key).toBe('status')
  })

  it('normalizes render payload from metadata-only shape', () => {
    const payload = normalizeReportRenderPayload({
      metadata: {
        module_key: 'platform',
        report_key: 'summary',
        name: 'Summary Report',
      },
      metrics: [{ metric_key: 'total', label: 'Total', value: 12 }],
      dataset: { rows: [{ id: '1', name: 'Alpha' }], total: 1 },
      columns: [{ column_key: 'name', label: 'Name', column_type: 'text' }],
      actions: [{ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' }],
    })

    expect(payload.report.name).toBe('Summary Report')
    expect(payload.metrics?.[0]?.value).toBe(12)
    expect(payload.datasets?.[0]?.rows?.[0]?.name).toBe('Alpha')
  })

  it('normalizes render payload with nested report object', () => {
    const payload = normalizeReportRenderPayload({
      report: {
        module_key: 'platform',
        report_key: 'summary',
        name: 'Summary Report',
      },
      sections: [{ section_key: 'table', label: 'Results', section_type: 'table' }],
      parameters: [{ parameter_key: 'from', label: 'From', parameter_type: 'date' }],
    })

    expect(payload.report.report_key).toBe('summary')
    expect(payload.sections[0]?.section_type).toBe('table')
    expect(payload.parameters?.[0]?.parameter_key).toBe('from')
  })

  it('normalizes layout sections when sections array is absent', () => {
    const payload = normalizeReportRenderPayload({
      report: { module_key: 'platform', report_key: 'summary', name: 'Summary' },
      layout: {
        sections: [{ section_key: 'summary', label: 'Summary', section_type: 'summary' }],
      },
    })

    expect(payload.sections[0]?.section_key).toBe('summary')
  })

  it('normalizes binding context flags', () => {
    const binding = normalizeReportBindingContext(
      {
        autoRender: true,
        exportEnabled: false,
        runEnabled: true,
        emptyStateMessage: 'No data',
      },
      'platform',
      'summary',
    )

    expect(binding.auto_render).toBe(true)
    expect(binding.export_enabled).toBe(false)
    expect(binding.run_enabled).toBe(true)
    expect(binding.empty_state_message).toBe('No data')
  })

  it('maps xlsx export format to excel request payload', () => {
    expect(buildReportExportRequest({ export_format: 'xlsx', parameters: { status: 'open' } })).toEqual({
      export_format: 'excel',
      parameters: { status: 'open' },
      metadata: undefined,
    })
  })

  it('normalizes run result camelCase keys', () => {
    const result = normalizeReportRunResult({
      publicId: 'run-1',
      status: 'completed',
      durationMs: 120,
    })

    expect(result.public_id).toBe('run-1')
    expect(result.duration_ms).toBe(120)
  })

  it('normalizes export result file reference', () => {
    const result = normalizeReportExportResult({
      exportFormat: 'pdf',
      status: 'completed',
      fileReference: { file_name: 'report.pdf' },
    })

    expect(result.export_format).toBe('pdf')
    expect(result.file_reference?.file_name).toBe('report.pdf')
  })

  it('normalizes section metrics and charts', () => {
    const section = normalizeReportSection({
      sectionKey: 'charts',
      label: 'Charts',
      sectionType: 'chart',
      charts: [{ chartKey: 'trend', label: 'Trend', chartType: 'line' }],
      metrics: [{ metricKey: 'total', label: 'Total', value: 5 }],
    })

    expect(section.charts?.[0]?.chart_key).toBe('trend')
    expect(section.metrics?.[0]?.metric_key).toBe('total')
  })

  it('tolerates empty render payload fields', () => {
    const payload = normalizeReportRenderPayload({})

    expect(payload.report.name).toBe('Report')
    expect(payload.sections).toEqual([])
    expect(payload.datasets).toEqual([])
  })
})
