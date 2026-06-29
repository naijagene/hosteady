import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { DashboardRenderPayload } from '@/api/types/dashboards'
import { DynamicDashboardRenderer } from '@/features/dashboards/components/DynamicDashboardRenderer'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

const payload: DashboardRenderPayload = {
  dashboard: {
    module_key: 'platform',
    dashboard_key: 'overview',
    name: 'Overview',
    description: 'Platform overview',
  },
  widgets: [
    {
      widget_key: 'total',
      label: 'Total records',
      widget_type: 'kpi_card',
      metric: { key: 'total', label: 'Total records', format: 'number' },
    },
    {
      widget_key: 'trend',
      label: 'Trend',
      widget_type: 'chart',
      chart_type: 'bar',
    },
  ],
  widget_data: [
    { widget_key: 'total', value: 12 },
    {
      widget_key: 'trend',
      chart: { type: 'bar', labels: ['A', 'B'], datasets: [{ key: 'series', data: [1, 2] }] },
    },
  ],
  layout: {
    columns: 12,
    items: [
      { widget_key: 'total', x: 0, y: 0, width: 4, height: 1 },
      { widget_key: 'trend', x: 4, y: 0, width: 8, height: 2 },
    ],
  },
  filters: [{ filter_key: 'status', label: 'Status', filter_type: 'text' }],
  actions: [{ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' }],
}

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: {
    preferences: [],
    favorites: [],
    recent_items: [],
    shortcuts: [],
    quick_actions: [],
    onboarding_state: {},
    theme_override: {},
    navigation_overrides: {},
    dashboard_overrides: {},
    table_overrides: {},
    notification_preferences_reference: {},
    warnings: [],
    source: 'runtime',
    runtime_context: {
      organization_public_id: null,
      workspace_public_id: null,
      membership_public_id: null,
      status: 'ready',
      missing_tables: [],
    },
  },
  navigationMenus: [],
  permissions: [],
  roles: [],
  user: null,
  organization: null,
  workspace: null,
  membership: null,
  application: null,
  unreadNotificationCount: 0,
  warnings: [],
  source: 'runtime',
}

function renderDashboard(ui: React.ReactNode) {
  useAuthStore.getState().setHydratedRuntime(runtime)
  return render(<HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>)
}

describe('DynamicDashboardRenderer', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('renders dashboard widgets from payload', () => {
    renderDashboard(<DynamicDashboardRenderer payload={payload} />)
    expect(screen.getByTestId('dynamic-dashboard-renderer')).toBeInTheDocument()
    expect(screen.getByText('Overview')).toBeInTheDocument()
    expect(screen.getByText('12')).toBeInTheDocument()
    expect(screen.getByLabelText('Trend chart')).toBeInTheDocument()
  })

  it('renders filter bar and toolbar', () => {
    renderDashboard(<DynamicDashboardRenderer payload={payload} />)
    expect(screen.getByTestId('dashboard-filter-bar')).toBeInTheDocument()
    expect(screen.getByTestId('dashboard-toolbar')).toBeInTheDocument()
  })

  it('shows empty state when no widgets', () => {
    renderDashboard(
      <DynamicDashboardRenderer
        payload={{ ...payload, widgets: [], widget_data: [] }}
        binding={{ moduleKey: 'platform', dashboardKey: 'overview', empty_state_message: 'No widgets' }}
      />,
    )
    expect(screen.getByText('No widgets')).toBeInTheDocument()
  })

  it('handles refresh action', async () => {
    const user = userEvent.setup()
    const onRefresh = vi.fn()
    renderDashboard(<DynamicDashboardRenderer payload={payload} onRefresh={onRefresh} />)
    await user.click(screen.getAllByRole('button', { name: 'Refresh' })[0]!)
    expect(onRefresh).toHaveBeenCalled()
  })

  it('shows export placeholder message', async () => {
    const user = userEvent.setup()
    renderDashboard(<DynamicDashboardRenderer payload={payload} />)
    await user.click(screen.getByRole('button', { name: 'Export' }))
    expect(screen.getByText(/Export is not implemented yet/)).toBeInTheDocument()
  })
})
