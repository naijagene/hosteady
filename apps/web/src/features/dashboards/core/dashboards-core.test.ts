import { describe, expect, it } from 'vitest'
import {
  normalizeDashboardBindingContext,
  normalizeDashboardDefinition,
  normalizeDashboardRenderPayload,
  normalizeDashboardWidget,
} from '@/api/types/dashboards'
import {
  getActionPlaceholderMessage,
  getDefaultDashboardActions,
  isSupportedDashboardActionType,
  resolveDashboardToolbarActions,
} from '@/features/dashboards/core/dashboard-actions'
import { toDashboardQueryError } from '@/features/dashboards/core/dashboard-errors'
import {
  createInitialFilterValues,
  getFilterValueKey,
  isSupportedFilterType,
  serializeDashboardFilters,
} from '@/features/dashboards/core/dashboard-filters'
import {
  buildFallbackLayout,
  getGridStyle,
  getLayoutColumns,
  getLayoutGap,
  resolveLayoutItems,
} from '@/features/dashboards/core/dashboard-layout'
import { buildMetricDisplay, formatMetricValue } from '@/features/dashboards/core/dashboard-metrics'
import {
  getDashboardDescription,
  getDashboardTitle,
  normalizeDashboardRenderModel,
} from '@/features/dashboards/core/dashboard-normalizer'
import {
  canRenderDashboard,
  filterActionsByPermission,
  filterWidgetsByPermission,
  hasPermission,
} from '@/features/dashboards/core/dashboard-permissions'
import {
  attachWidgetData,
  normalizeWidgetType,
  resolveDashboardWidgets,
} from '@/features/dashboards/core/dashboard-widgets'
import { ApiError } from '@/api/errors'

describe('dashboard API normalization', () => {
  it('normalizes dashboard definition camelCase keys', () => {
    const definition = normalizeDashboardDefinition({
      moduleKey: 'platform',
      dashboardKey: 'overview',
      name: 'Overview',
      widgets: [{ widgetKey: 'kpi', name: 'KPI', widgetType: 'kpi_card' }],
    })

    expect(definition.module_key).toBe('platform')
    expect(definition.widgets?.[0]?.widget_key).toBe('kpi')
  })

  it('normalizes render payload from backend metadata shape', () => {
    const payload = normalizeDashboardRenderPayload({
      metadata: {
        module_key: 'platform',
        dashboard_key: 'overview',
        name: 'Overview',
      },
      widgets: [{ widget_key: 'total', name: 'Total', widget_type: 'kpi_card' }],
      widget_data: [{ widget_key: 'total', value: 12 }],
      layout: { columns: 12, items: [{ widget_key: 'total', x: 0, y: 0, width: 4, height: 1 }] },
      filters: [{ field_key: 'status', label: 'Status', filter_type: 'select' }],
      actions: [{ key: 'refresh', label: 'Refresh', type: 'refresh' }],
    })

    expect(payload.dashboard.name).toBe('Overview')
    expect(payload.widget_data?.[0]?.value).toBe(12)
    expect(payload.layout?.columns).toBe(12)
  })

  it('normalizes binding context flags', () => {
    const binding = normalizeDashboardBindingContext(
      {
        autoRender: true,
        refreshEnabled: false,
        personalizationEnabled: true,
        emptyStateMessage: 'Nothing here',
      },
      'platform',
      'overview',
    )

    expect(binding.auto_render).toBe(true)
    expect(binding.refresh_enabled).toBe(false)
    expect(binding.personalization_enabled).toBe(true)
    expect(binding.empty_state_message).toBe('Nothing here')
  })
})

describe('dashboard normalizer model', () => {
  it('builds widget data map', () => {
    const payload = normalizeDashboardRenderPayload({
      dashboard: { module_key: 'platform', dashboard_key: 'overview', name: 'Overview' },
      widgets: [{ widget_key: 'kpi', label: 'KPI', widget_type: 'metric' }],
      widget_data: [{ widget_key: 'kpi', value: 5 }],
    })
    const model = normalizeDashboardRenderModel(payload)

    expect(model.widgetDataMap.get('kpi')?.value).toBe(5)
    expect(getDashboardTitle(payload)).toBe('Overview')
    expect(getDashboardDescription(payload)).toBeNull()
  })
})

describe('dashboard layout', () => {
  it('uses fallback columns and gap', () => {
    expect(getLayoutColumns(null)).toBe(12)
    expect(getLayoutGap({ gap: 8 })).toBe('8px')
    expect(getLayoutGap({ gap: '1.5rem' })).toBe('1.5rem')
    expect(getLayoutColumns({ columns: 6 })).toBe(6)
  })

  it('resolves layout items for widgets', () => {
    const placements = resolveLayoutItems(
      {
        items: [{ widget_key: 'kpi', x: 0, y: 0, width: 6, height: 2 }],
      },
      [
        normalizeDashboardWidget({
          widget_key: 'kpi',
          label: 'KPI',
          widget_type: 'metric',
        }) as never,
      ],
    )

    expect(placements[0]?.columnSpan).toBe(6)
    expect(placements[0]?.rowSpan).toBe(2)
    expect(placements[0]?.columnStart).toBe(1)
  })

  it('builds fallback layout', () => {
    expect(buildFallbackLayout([{ widget_key: 'a' } as never])[0]?.columnSpan).toBe(4)
    expect(buildFallbackLayout([{ widget_key: 'a' } as never, { widget_key: 'b' } as never])).toHaveLength(2)
  })

  it('builds grid style from placement', () => {
    expect(getGridStyle({
      widgetKey: 'kpi',
      columnStart: 2,
      columnSpan: 4,
      rowStart: 1,
      rowSpan: 2,
    })).toEqual({
      gridColumn: '2 / span 4',
      gridRow: '1 / span 2',
    })
  })
})

describe('dashboard widget aliases', () => {
  it.each([
    ['metric', 'metric'],
    ['kpi_card', 'metric'],
    ['chart_card', 'chart'],
    ['table_widget', 'table'],
    ['notification_feed', 'notification'],
    ['quick_actions', 'quick_actions'],
    ['recent_items', 'recent_items'],
    ['favorites', 'favorites'],
    ['line_chart', 'chart'],
  ])('maps %s to %s', (input, expected) => {
    expect(normalizeWidgetType(input)).toBe(expected)
  })
})

describe('dashboard widget visibility', () => {
  it('hides widgets from personalization overrides', () => {
    const widgets = resolveDashboardWidgets(
      [
        { widget_key: 'visible', label: 'Visible', widget_type: 'metric' },
        { widget_key: 'hidden', label: 'Hidden', widget_type: 'metric' },
      ],
      { hiddenKeys: new Set(['hidden']) },
    )

    expect(widgets).toHaveLength(1)
    expect(widgets[0]?.widget_key).toBe('visible')
  })
})

describe('dashboard widgets', () => {
  it('orders widgets and attaches data', () => {
    const widgets = resolveDashboardWidgets(
      [
        { widget_key: 'b', label: 'B', widget_type: 'metric', sort_order: 2 },
        { widget_key: 'a', label: 'A', widget_type: 'metric', sort_order: 1 },
      ],
      { order: ['b', 'a'] },
    )
    const dataMap = new Map([['a', { widget_key: 'a', value: 1 }]])
    const attached = attachWidgetData(widgets, dataMap)

    expect(widgets[0]?.widget_key).toBe('b')
    expect(attached[1]?.data?.value).toBe(1)
  })
})

describe('dashboard permissions', () => {
  it('allows render when permission list includes dashboards.render', () => {
    expect(canRenderDashboard(['dashboards.render'])).toBe(true)
  })
})

describe('dashboard metrics', () => {
  it('formats metric values', () => {
    expect(formatMetricValue(null)).toBe('—')
    expect(formatMetricValue(10, 'percent')).toBe('10%')
  })

  it('builds metric display from widget data', () => {
    const display = buildMetricDisplay(
      { key: 'total', label: 'Total records', format: 'number' },
      { widget_key: 'total', value: 42, metadata: { trend: '+5%' } },
      'Metric',
    )

    expect(display.value).toBe('42')
    expect(display.trend).toBe('+5%')
  })
})

describe('dashboard filters and actions', () => {
  it('serializes filter values', () => {
    const filters = serializeDashboardFilters(
      [{ filter_key: 'status', label: 'Status', filter_type: 'text' }],
      { status: 'active' },
    )
    expect(filters[0]?.value).toBe('active')
    expect(getFilterValueKey(filters[0]!)).toBe('status')
  })

  it('creates initial filter values', () => {
    expect(
      createInitialFilterValues([
        { filter_key: 'q', label: 'Search', filter_type: 'text', value: 'abc' },
      ]).q,
    ).toBe('abc')
  })

  it('supports filter and action types', () => {
    expect(isSupportedFilterType('date_range')).toBe(true)
    expect(isSupportedFilterType('text')).toBe(true)
    expect(isSupportedFilterType('select')).toBe(true)
    expect(isSupportedFilterType('boolean')).toBe(true)
    expect(isSupportedDashboardActionType('refresh')).toBe(true)
    expect(isSupportedDashboardActionType('open_report')).toBe(true)
    expect(isSupportedDashboardActionType('start_workflow')).toBe(true)
    expect(getActionPlaceholderMessage({ action_key: 'export', label: 'Export', action_type: 'export' })).toContain(
      'Export',
    )
    expect(getActionPlaceholderMessage({ action_key: 'report', label: 'Report', action_type: 'open_report' })).toContain(
      'report',
    )
    expect(getActionPlaceholderMessage({ action_key: 'workflow', label: 'Workflow', action_type: 'start_workflow' })).toContain(
      'workflow',
    )
  })

  it('resolves toolbar actions and defaults', () => {
    expect(getDefaultDashboardActions().map((action) => action.action_type)).toEqual(['refresh', 'export'])
    expect(
      resolveDashboardToolbarActions([
        { action_key: 'refresh', label: 'Refresh', action_type: 'refresh' },
        { action_key: 'row', label: 'Row', action_type: 'row' },
      ]),
    ).toHaveLength(1)
  })
})

describe('dashboard permissions and errors', () => {
  it('filters widgets and actions by permission', () => {
    expect(
      filterWidgetsByPermission(
        [{ widget_key: 'a', label: 'A', widget_type: 'metric', permission: 'dash.read' }],
        ['dash.read'],
      ),
    ).toHaveLength(1)
    expect(hasPermission(['a'], 'b')).toBe(false)
    expect(
      filterActionsByPermission(
        [{ action_key: 'x', label: 'X', action_type: 'custom', permission: 'dash.manage' }],
        [],
      ),
    ).toHaveLength(0)
  })

  it('checks dashboard render permission object', () => {
    expect(canRenderDashboard({ read: true, render: true })).toBe(true)
    expect(canRenderDashboard({ read: true, render: false })).toBe(false)
  })

  it('maps dashboard query errors', () => {
    expect(
      toDashboardQueryError(new ApiError('Failed', { status: 500 })).message,
    ).toBe('Failed')
  })
})
