import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import * as dashboardsApi from '@/api/endpoints/dashboards'
import { DirectDashboardPage } from '@/features/dashboards/pages/DirectDashboardPage'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual('@tanstack/react-router')
  return {
    ...actual,
    useParams: () => ({ moduleKey: 'platform', dashboardKey: 'overview' }),
  }
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
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

describe('DirectDashboardPage', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    useAuthStore.getState().setHydratedRuntime(runtime)
  })

  it('loads dashboard render payload and renders dynamic dashboard', async () => {
    vi.spyOn(dashboardsApi, 'fetchDashboardRender').mockResolvedValue({
      dashboard: {
        module_key: 'platform',
        dashboard_key: 'overview',
        name: 'Overview',
      },
      widgets: [
        {
          widget_key: 'kpi',
          label: 'Records',
          widget_type: 'metric',
        },
      ],
      widget_data: [{ widget_key: 'kpi', value: 9 }],
    })

    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
    render(
      <QueryClientProvider client={client}>
        <HydratedRuntimeProvider>
          <DirectDashboardPage />
        </HydratedRuntimeProvider>
      </QueryClientProvider>,
    )

    await waitFor(() => {
      expect(screen.getByTestId('dynamic-dashboard-renderer')).toBeInTheDocument()
    })
    expect(screen.getByText('9')).toBeInTheDocument()
  })
})
