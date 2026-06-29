import { describe, expect, it } from 'vitest'
import { ApiError } from '@/api/errors'
import { normalizeReportRenderPayload } from '@/api/types/reports'
import {
  getDefaultReportActions,
  getReportActionPlaceholder,
  isSupportedReportActionType,
  resolveReportToolbarActions,
} from '@/features/reports/core/report-actions'
import { getChartDatasetSummary, isSupportedChartType } from '@/features/reports/core/report-charts'
import { toReportQueryError } from '@/features/reports/core/report-errors'
import { isSupportedReportFilterType, serializeReportFilters } from '@/features/reports/core/report-filters'
import { buildReportMetricDisplay, formatReportMetricValue } from '@/features/reports/core/report-metrics'
import {
  getReportDescription,
  getReportTitle,
  normalizeReportRenderModel,
} from '@/features/reports/core/report-normalizer'
import {
  createInitialParameterValues,
  getParameterValueKey,
  isSupportedParameterType,
  serializeReportParameters,
  validateRequiredParameters,
} from '@/features/reports/core/report-parameters'
import {
  canExportReport,
  canRunReport,
  filterActionsByPermission,
  hasPermission,
} from '@/features/reports/core/report-permissions'
import { flattenSections, normalizeSectionType, resolveReportSections } from '@/features/reports/core/report-sections'

describe('report normalizer', () => {
  it('builds normalized report model', () => {
    const payload = normalizeReportRenderPayload({
      report: { module_key: 'platform', report_key: 'summary', name: 'Summary' },
      sections: [{ section_key: 'summary', label: 'Summary', section_type: 'summary' }],
      actions: [{ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' }],
    })

    const model = normalizeReportRenderModel(payload)
    expect(model.sections).toHaveLength(1)
    expect(model.actions[0]?.action_key).toBe('refresh')
  })

  it('returns report title and description', () => {
    const payload = normalizeReportRenderPayload({
      report: {
        module_key: 'platform',
        report_key: 'summary',
        name: 'Summary',
        description: 'Platform summary',
      },
    })

    expect(getReportTitle(payload)).toBe('Summary')
    expect(getReportDescription(payload)).toBe('Platform summary')
  })
})

describe('report parameters', () => {
  const parameters = [
    { parameter_key: 'status', label: 'Status', parameter_type: 'select', required: true },
    { parameter_key: 'hidden', label: 'Hidden', parameter_type: 'hidden', default_value: 'x' },
  ]

  it('creates initial parameter values', () => {
    expect(createInitialParameterValues(parameters)).toEqual({ status: '', hidden: 'x' })
  })

  it('validates required parameters', () => {
    expect(validateRequiredParameters(parameters, { status: '', hidden: 'x' })).toEqual([
      'Status is required',
    ])
  })

  it('serializes parameters and preserves hidden defaults', () => {
    expect(
      serializeReportParameters(parameters, { status: 'open', hidden: 'ignored' }),
    ).toEqual({ status: 'open', hidden: 'x' })
  })

  it('supports parameter type checks', () => {
    expect(isSupportedParameterType('date_range')).toBe(true)
    expect(isSupportedParameterType('unknown')).toBe(false)
  })

  it('uses parameter key helper', () => {
    expect(getParameterValueKey(parameters[0]!)).toBe('status')
  })
})

describe('report sections', () => {
  it('normalizes supported section types', () => {
    expect(normalizeSectionType('TABLE')).toBe('table')
    expect(normalizeSectionType('unknown')).toBe('custom')
  })

  it('synthesizes sections from metrics dataset and charts', () => {
    const payload = normalizeReportRenderPayload({
      report: { module_key: 'platform', report_key: 'summary', name: 'Summary' },
      metrics: [{ metric_key: 'total', label: 'Total', value: 3 }],
      dataset: { rows: [{ id: '1' }], total: 1 },
      columns: [{ column_key: 'id', label: 'ID', column_type: 'text' }],
      charts: [{ chart_key: 'trend', label: 'Trend', chart_type: 'line' }],
    })

    const sections = resolveReportSections(payload)
    expect(sections.map((section) => section.section_type)).toEqual(['summary', 'table', 'chart'])
  })

  it('flattens grouped sections', () => {
    const sections = flattenSections([
      {
        section_key: 'group',
        label: 'Group',
        section_type: 'group',
        sections: [{ section_key: 'child', label: 'Child', section_type: 'text' }],
      },
    ])

    expect(sections).toHaveLength(2)
  })
})

describe('report metrics', () => {
  it('formats metric values', () => {
    expect(formatReportMetricValue(null)).toBe('—')
    expect(formatReportMetricValue(42, 'percent')).toBe('42%')
  })

  it('builds metric display metadata', () => {
    const display = buildReportMetricDisplay({
      metric_key: 'total',
      label: 'Total',
      value: 10,
      prefix: '$',
      suffix: ' USD',
      trend: 'up',
      comparison: 'vs last week',
      status: 'healthy',
    })

    expect(display.value).toBe('10')
    expect(display.prefix).toBe('$')
    expect(display.trend).toBe('up')
  })
})

describe('report charts', () => {
  it('detects supported chart types', () => {
    expect(isSupportedChartType('donut')).toBe(true)
    expect(isSupportedChartType('3d')).toBe(false)
  })

  it('summarizes chart datasets', () => {
    const summary = getChartDatasetSummary({
      chart_key: 'trend',
      label: 'Trend',
      chart_type: 'line',
      labels: ['A', 'B'],
      datasets: [{ data: [1, 2] }],
    })

    expect(summary).toBe('2 labels · 2 points')
  })
})

describe('report filters', () => {
  it('supports filter types', () => {
    expect(isSupportedReportFilterType('date_range')).toBe(true)
    expect(isSupportedReportFilterType('lookup')).toBe(false)
  })

  it('serializes filter values', () => {
    const filters = serializeReportFilters(
      [{ filter_key: 'status', label: 'Status', filter_type: 'select' }],
      { status: 'open' },
    )

    expect(filters[0]?.value).toBe('open')
  })
})

describe('report actions', () => {
  it('supports action types', () => {
    expect(isSupportedReportActionType('refresh')).toBe(true)
    expect(isSupportedReportActionType('delete')).toBe(false)
  })

  it('returns default toolbar actions', () => {
    expect(getDefaultReportActions()).toHaveLength(3)
    expect(getDefaultReportActions({ exportEnabled: false }).map((action) => action.action_type)).toEqual([
      'run',
      'refresh',
    ])
  })

  it('filters row actions from toolbar', () => {
    const actions = resolveReportToolbarActions([
      { action_key: 'run', label: 'Run', action_type: 'run' },
      { action_key: 'row', label: 'Row', action_type: 'row' },
    ])

    expect(actions).toHaveLength(1)
  })

  it('returns action placeholders', () => {
    expect(getReportActionPlaceholder({ action_key: 'email', label: 'Email', action_type: 'email' })).toContain(
      'not implemented',
    )
  })
})

describe('report permissions', () => {
  it('checks permission helper', () => {
    expect(hasPermission(['reports.read'], 'reports.read')).toBe(true)
    expect(hasPermission(['reports.read'], 'reports.export')).toBe(false)
  })

  it('filters actions by permission', () => {
    const actions = filterActionsByPermission(
      [
        { action_key: 'run', label: 'Run', action_type: 'run', permission: 'reports.run' },
        { action_key: 'refresh', label: 'Refresh', action_type: 'refresh', permission: 'reports.read' },
      ],
      ['reports.run'],
    )

    expect(actions.map((action) => action.action_key)).toEqual(['run'])
  })

  it('evaluates run and export permissions from object shape', () => {
    expect(canRunReport({ run: false })).toBe(false)
    expect(canExportReport({ export: false })).toBe(false)
  })
})

describe('report errors', () => {
  it('normalizes ApiError', () => {
    expect(toReportQueryError(new ApiError('Failed', { status: 500 })).message).toBe('Failed')
  })

  it('normalizes generic errors', () => {
    expect(toReportQueryError(new Error('Boom')).message).toBe('Boom')
  })

  it('falls back for unknown errors', () => {
    expect(toReportQueryError(undefined).message).toBe('Unable to load report.')
  })
})
