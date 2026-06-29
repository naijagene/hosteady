import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import type { ReportRenderPayload } from '@/api/types/reports'
import * as reportsApi from '@/api/endpoints/reports'
import { DynamicReportViewer } from '@/features/reports/components/DynamicReportViewer'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

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

const payload: ReportRenderPayload = {
  report: {
    module_key: 'platform',
    report_key: 'summary',
    name: 'Summary Report',
    description: 'Platform summary',
  },
  sections: [
    {
      section_key: 'summary',
      label: 'Summary',
      section_type: 'summary',
      metrics: [{ metric_key: 'total', label: 'Total', value: 7 }],
    },
    {
      section_key: 'table',
      label: 'Results',
      section_type: 'table',
      columns: [{ column_key: 'name', label: 'Name', column_type: 'text' }],
      rows: [{ name: 'Alpha' }],
    },
  ],
  parameters: [{ parameter_key: 'status', label: 'Status', parameter_type: 'text', required: true }],
}

function renderViewer(overrides?: Partial<ReportRenderPayload>) {
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <HydratedRuntimeProvider>
      <DynamicReportViewer
        payload={{ ...payload, ...overrides }}
        binding={{
          moduleKey: 'platform',
          reportKey: 'summary',
          export_enabled: true,
          run_enabled: true,
        }}
        onRefresh={vi.fn()}
      />
    </HydratedRuntimeProvider>,
  )
}

describe('DynamicReportViewer', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('renders report metadata sections and parameters', () => {
    renderViewer()
    expect(screen.getByTestId('dynamic-report-viewer')).toBeInTheDocument()
    expect(screen.getByText('Summary Report')).toBeInTheDocument()
    expect(screen.getByLabelText('Status')).toBeInTheDocument()
    expect(screen.getByText('7')).toBeInTheDocument()
    expect(screen.getByText('Alpha')).toBeInTheDocument()
  })

  it('shows empty state when no sections exist', () => {
    renderViewer({ sections: [] })
    expect(screen.getByTestId('report-empty-state')).toBeInTheDocument()
  })

  it('shows custom empty state message from binding', () => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    render(
      <HydratedRuntimeProvider>
        <DynamicReportViewer
          payload={{ ...payload, sections: [] }}
          binding={{
            moduleKey: 'platform',
            reportKey: 'summary',
            empty_state_message: 'No report data yet',
          }}
        />
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByText('No report data yet')).toBeInTheDocument()
  })

  it('applies parameter validation warnings', async () => {
    const user = userEvent.setup()
    renderViewer()

    await user.click(screen.getByRole('button', { name: 'Apply report parameters' }))
    expect(screen.getByText('Status is required')).toBeInTheDocument()
  })

  it('runs report when run action is clicked', async () => {
    const user = userEvent.setup()
    vi.spyOn(reportsApi, 'runReport').mockResolvedValue({ status: 'completed', duration_ms: 90 })

    renderViewer()
    await user.click(screen.getByRole('button', { name: 'Run' }))

    await waitFor(() => {
      expect(screen.getByText(/completed/)).toBeInTheDocument()
    })
  })

  it('exports report defensively and shows file metadata', async () => {
    const user = userEvent.setup()
    vi.spyOn(reportsApi, 'exportReport').mockResolvedValue({
      export_format: 'pdf',
      status: 'completed',
      file_reference: { file_name: 'summary.pdf' },
    })

    renderViewer()
    await user.click(screen.getByLabelText('Export report'))
    await user.click(screen.getByRole('button', { name: 'Export as PDF' }))

    await waitFor(() => {
      expect(screen.getByText('summary.pdf (pdf)')).toBeInTheDocument()
    })
  })

  it('shows action placeholder for schedule action', async () => {
    const user = userEvent.setup()
    renderViewer({
      actions: [{ action_key: 'schedule', label: 'Schedule', action_type: 'schedule' }],
    })

    await user.click(screen.getByRole('button', { name: 'Schedule' }))
    expect(screen.getByText(/not implemented/i)).toBeInTheDocument()
  })
})
