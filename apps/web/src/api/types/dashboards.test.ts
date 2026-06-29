import { describe, expect, it } from 'vitest'
import {
  normalizeDashboardChart,
  normalizeDashboardRenderPayload,
  normalizeDashboardWidgetData,
} from '@/api/types/dashboards'

describe('dashboards API types', () => {
  it('normalizes widget data with chart payload', () => {
    const data = normalizeDashboardWidgetData({
      widgetKey: 'chart',
      value: null,
      chart: {
        type: 'bar',
        labels: ['A', 'B'],
        datasets: [{ key: 'series', data: [1, 2] }],
      },
    })

    expect(data.widget_key).toBe('chart')
    expect(data.chart?.type).toBe('bar')
    expect(data.chart?.labels).toEqual(['A', 'B'])
  })

  it('normalizes chart datasets', () => {
    const chart = normalizeDashboardChart({
      type: 'line',
      datasets: [{ key: 'primary', label: 'Primary', data: [3, 4] }],
    })

    expect(chart.datasets?.[0]?.key).toBe('primary')
  })

  it('tolerates empty render payload sections', () => {
    const payload = normalizeDashboardRenderPayload({
      dashboard: { module_key: 'platform', dashboard_key: 'home', name: 'Home' },
      layout: {},
      widgets: [],
      datasets: [],
      metrics: [],
      actions: [],
      filters: [],
      metadata: {},
    })

    expect(payload.widgets).toEqual([])
    expect(payload.dashboard.name).toBe('Home')
  })

  it.each([
    'line',
    'bar',
    'pie',
    'donut',
    'area',
    'scatter',
    'radar',
    'table',
    'number',
    'custom',
  ])('accepts chart type %s in normalized chart payload', (type) => {
    const chart = normalizeDashboardChart({ type, labels: ['A'], datasets: [{ key: 'series', data: [1] }] })
    expect(chart.type).toBe(type)
  })
})
