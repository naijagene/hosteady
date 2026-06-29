import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import * as reportsApi from '@/api/endpoints/reports'
import { DirectReportPage } from '@/features/reports/pages/DirectReportPage'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual('@tanstack/react-router')
  return {
    ...actual,
    useParams: () => ({ moduleKey: 'platform', reportKey: 'summary' }),
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

function renderPage() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>
        <DirectReportPage />
      </HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('DirectReportPage', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('loads and renders report viewer', async () => {
    vi.spyOn(reportsApi, 'fetchReportRender').mockResolvedValue({
      report: {
        module_key: 'platform',
        report_key: 'summary',
        name: 'Summary Report',
      },
      sections: [
        {
          section_key: 'summary',
          label: 'Summary',
          section_type: 'summary',
          metrics: [{ metric_key: 'total', label: 'Total', value: 3 }],
        },
      ],
    })

    renderPage()

    await waitFor(() => {
      expect(screen.getByTestId('dynamic-report-viewer')).toBeInTheDocument()
    })
    expect(screen.getByText('Summary Report')).toBeInTheDocument()
  })

  it('renders loading state while fetching', () => {
    vi.spyOn(reportsApi, 'fetchReportRender').mockReturnValue(new Promise(() => undefined))

    renderPage()
    expect(screen.getByTestId('report-loading-state')).toBeInTheDocument()
  })

  it('renders error state when fetch fails', async () => {
    vi.spyOn(reportsApi, 'fetchReportRender').mockRejectedValue(new Error('Report unavailable'))

    renderPage()

    await waitFor(() => {
      expect(screen.getByTestId('report-error-state')).toHaveTextContent('Report unavailable')
    })
  })
})
