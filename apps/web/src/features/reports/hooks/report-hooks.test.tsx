import { beforeEach, describe, expect, it, vi } from 'vitest'
import { act, renderHook } from '@testing-library/react'
import * as reportsApi from '@/api/endpoints/reports'
import { useReportActions } from '@/features/reports/hooks/useReportActions'
import { useReportExport } from '@/features/reports/hooks/useReportExport'
import { useReportParameters } from '@/features/reports/hooks/useReportParameters'
import { useReportRender } from '@/features/reports/hooks/useReportRender'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import type { ReportRenderPayload } from '@/api/types/reports'
import type { ReactNode } from 'react'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['reports.run', 'reports.export'],
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
    description: 'Summary',
  },
  sections: [],
  actions: [{ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' }],
  parameters: [{ parameter_key: 'status', label: 'Status', parameter_type: 'text', required: true }],
}

function wrapper({ children }: { children: ReactNode }) {
  useAuthStore.getState().setHydratedRuntime(runtime)
  return <HydratedRuntimeProvider>{children}</HydratedRuntimeProvider>
}

describe('useReportParameters', () => {
  it('applies and resets parameter values', () => {
    const { result } = renderHook(() => useReportParameters(payload.parameters ?? []))

    act(() => {
      result.current.setParameterValue('status', 'open')
    })

    act(() => {
      result.current.applyParameters()
    })

    expect(result.current.applied.status).toBe('open')

    act(() => {
      result.current.resetParameters()
    })

    expect(result.current.applied.status).toBe('')
  })

  it('returns validation warnings for required parameters', () => {
    const { result } = renderHook(() => useReportParameters(payload.parameters ?? []))

    act(() => {
      result.current.applyParameters()
    })

    expect(result.current.warnings).toEqual(['Status is required'])
  })
})

describe('useReportActions', () => {
  it('calls refresh handler', () => {
    const onRefresh = vi.fn()
    const { result } = renderHook(() => useReportActions({ onRefresh }))

    act(() => {
      result.current.handleAction({ action_key: 'refresh', label: 'Refresh', action_type: 'refresh' })
    })

    expect(onRefresh).toHaveBeenCalled()
  })

  it('returns placeholder for unsupported actions', () => {
    const { result } = renderHook(() => useReportActions())

    act(() => {
      result.current.handleAction({ action_key: 'email', label: 'Email', action_type: 'email' })
    })

    expect(result.current.message).toContain('not implemented')
  })
})

describe('useReportExport', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('calls export endpoint defensively', async () => {
    vi.spyOn(reportsApi, 'exportReport').mockResolvedValue({
      export_format: 'pdf',
      status: 'completed',
      file_reference: { file_name: 'report.pdf' },
    })

    const { result } = renderHook(() =>
      useReportExport({ moduleKey: 'platform', reportKey: 'summary', enabled: true }),
    )

    await act(async () => {
      await result.current.exportReportFile({ export_format: 'pdf' })
    })

    expect(result.current.result?.file_reference?.file_name).toBe('report.pdf')
  })

  it('captures export errors', async () => {
    vi.spyOn(reportsApi, 'exportReport').mockRejectedValue(new Error('Export failed'))

    const { result } = renderHook(() =>
      useReportExport({ moduleKey: 'platform', reportKey: 'summary', enabled: true }),
    )

    await act(async () => {
      await result.current.exportReportFile({ export_format: 'csv' })
    })

    expect(result.current.error).toBe('Export failed')
  })

  it('blocks export when disabled', async () => {
    const { result } = renderHook(() =>
      useReportExport({ moduleKey: 'platform', reportKey: 'summary', enabled: false }),
    )

    await act(async () => {
      await result.current.exportReportFile({ export_format: 'json' })
    })

    expect(result.current.error).toBe('Export is not enabled for this report.')
  })
})

describe('useReportRender', () => {
  it('resolves report title and default actions', () => {
    const { result } = renderHook(
      () =>
        useReportRender({
          payload,
          binding: { moduleKey: 'platform', reportKey: 'summary', export_enabled: true, run_enabled: true },
        }),
      { wrapper },
    )

    expect(result.current.title).toBe('Summary Report')
    expect(result.current.actions.some((action) => action.action_type === 'run')).toBe(true)
  })

  it('respects disabled run/export binding flags', () => {
    const { result } = renderHook(
      () =>
        useReportRender({
          payload,
          binding: { moduleKey: 'platform', reportKey: 'summary', export_enabled: false, run_enabled: false },
        }),
      { wrapper },
    )

    expect(result.current.actions.some((action) => action.action_type === 'run')).toBe(false)
    expect(result.current.actions.some((action) => action.action_type === 'export')).toBe(false)
  })
})
