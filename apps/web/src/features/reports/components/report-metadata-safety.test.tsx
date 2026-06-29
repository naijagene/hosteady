import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { normalizeReportRenderPayload } from '@/api/types/reports'
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

describe('report malformed metadata safety', () => {
  it('does not crash on malformed render payload', () => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    const payload = normalizeReportRenderPayload({
      metadata: null,
      sections: [{ invalid: true }],
      metrics: [{ value: 'x' }],
    })

    render(
      <HydratedRuntimeProvider>
        <DynamicReportViewer payload={payload} />
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByTestId('dynamic-report-viewer')).toBeInTheDocument()
  })

  it('renders custom placeholders for malformed sections', () => {
    useAuthStore.getState().setHydratedRuntime(runtime)
    const payload = normalizeReportRenderPayload({
      report: { module_key: 'platform', report_key: 'summary', name: 'Summary' },
      sections: [{ section_key: 'bad', label: 'Bad Section', section_type: 'weird' }],
    })

    render(
      <HydratedRuntimeProvider>
        <DynamicReportViewer payload={payload} />
      </HydratedRuntimeProvider>,
    )

    expect(screen.getByTestId('report-custom-section')).toBeInTheDocument()
  })
})
