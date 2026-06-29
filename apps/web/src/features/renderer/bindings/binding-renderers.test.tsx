import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { RendererContextProvider } from '@/features/renderer/core/RendererContext'
import { FormBindingRenderer } from '@/features/renderer/bindings/FormBindingRenderer'
import { TableBindingRenderer } from '@/features/renderer/bindings/TableBindingRenderer'
import { DashboardBindingRenderer } from '@/features/renderer/bindings/DashboardBindingRenderer'
import { ReportBindingRenderer } from '@/features/renderer/bindings/ReportBindingRenderer'
import { DocumentBindingRenderer } from '@/features/renderer/bindings/DocumentBindingRenderer'
import { WorkflowBindingRenderer } from '@/features/renderer/bindings/WorkflowBindingRenderer'
import * as formsApi from '@/api/endpoints/forms'
import * as tablesApi from '@/api/endpoints/tables'
import * as dashboardsApi from '@/api/endpoints/dashboards'
import * as reportsApi from '@/api/endpoints/reports'
import * as documentsApi from '@/api/endpoints/documents'
import * as workflowsApi from '@/api/endpoints/workflows'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

import type { UiComponent } from '@/api/types/ui'

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

function renderBinding(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>
        <RendererContextProvider moduleKey="platform">{ui}</RendererContextProvider>
      </HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

const formComponent: UiComponent = {
  public_id: '1',
  component_key: 'profile-form',
  name: 'Profile',
  component_type: 'form',
  binding_type: 'form',
  binding_config: { module_key: 'platform', resource_key: 'profile' },
}

describe('Binding renderers', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('FormBindingRenderer loads form metadata', async () => {
    vi.spyOn(formsApi, 'fetchFormDefinition').mockResolvedValue({
      module_key: 'platform',
      form_key: 'profile',
      name: 'Profile Form',
      fields: [{ field_key: 'name', label: 'Name', field_type: 'text' }],
      sections: [{ section_key: 'main', label: 'Main', fields: ['name'] }],
    })

    renderBinding(<FormBindingRenderer component={formComponent} />)

    await waitFor(() => {
      expect(screen.getByTestId('form-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByTestId('dynamic-form-renderer')).toBeInTheDocument()
    expect(screen.getByLabelText(/Name/)).toBeInTheDocument()
  })

  it('TableBindingRenderer loads table metadata', async () => {
    vi.spyOn(tablesApi, 'fetchTableDefinition').mockResolvedValue({
      module_key: 'platform',
      table_key: 'users',
      name: 'Users',
      columns: [{ column_key: 'email', label: 'Email', column_type: 'text' }],
    })
    vi.spyOn(tablesApi, 'queryTable').mockResolvedValue({
      rows: [{ public_id: '1', values: { email: 'ada@test.com' } }],
      total: 1,
      page: 1,
      per_page: 25,
      last_page: 1,
    })

    renderBinding(
      <TableBindingRenderer
        component={{
          ...formComponent,
          component_type: 'table',
          binding_type: 'table',
          binding_config: {
            module_key: 'platform',
            resource_key: 'users',
            query_enabled: true,
          },
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('table-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByTestId('dynamic-table-renderer')).toBeInTheDocument()
    await waitFor(() => {
      expect(screen.getByText('ada@test.com')).toBeInTheDocument()
    })
  })

  it('DashboardBindingRenderer renders dynamic dashboard', async () => {
    vi.spyOn(dashboardsApi, 'fetchDashboardRender').mockResolvedValue({
      dashboard: {
        module_key: 'platform',
        dashboard_key: 'overview',
        name: 'Overview',
      },
      widgets: [
        {
          widget_key: 'kpi',
          label: 'KPI',
          widget_type: 'metric',
        },
      ],
      widget_data: [{ widget_key: 'kpi', value: 7 }],
    })

    renderBinding(
      <DashboardBindingRenderer
        component={{
          ...formComponent,
          component_type: 'dashboard',
          binding_type: 'dashboard',
          binding_config: {
            module_key: 'platform',
            resource_key: 'overview',
            auto_render: true,
          },
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('dashboard-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByTestId('dynamic-dashboard-renderer')).toBeInTheDocument()
    await waitFor(() => {
      expect(screen.getByText('7')).toBeInTheDocument()
    })
  })

  it('ReportBindingRenderer renders dynamic report viewer', async () => {
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
          metrics: [{ metric_key: 'total', label: 'Total', value: 9 }],
        },
      ],
    })

    renderBinding(
      <ReportBindingRenderer
        component={{
          ...formComponent,
          component_type: 'report',
          binding_type: 'report',
          binding_config: {
            module_key: 'platform',
            resource_key: 'summary',
            auto_render: true,
          },
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('report-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByTestId('dynamic-report-viewer')).toBeInTheDocument()
    expect(screen.getByText('Summary Report')).toBeInTheDocument()
    expect(screen.getByText('9')).toBeInTheDocument()
  })

  it('DocumentBindingRenderer renders document manager', async () => {
    vi.spyOn(documentsApi, 'fetchDocuments').mockResolvedValue({
      items: [{ public_id: 'doc-1', title: 'Policy Document' }],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
    })

    renderBinding(
      <DocumentBindingRenderer
        component={{
          public_id: '1',
          component_key: 'docs',
          name: 'Documents',
          component_type: 'document_list',
          binding_config: { mode: 'list', query_enabled: true },
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('document-binding-renderer')).toBeInTheDocument()
    })
    await waitFor(() => {
      expect(screen.getByTestId('document-manager')).toBeInTheDocument()
    })
    expect(screen.getByText('Policy Document')).toBeInTheDocument()
  })

  it('WorkflowBindingRenderer lists workflow placeholders', async () => {
    vi.spyOn(workflowsApi, 'fetchWorkflowDefinitions').mockResolvedValue([
      { public_id: 'wf-1', name: 'Approval Flow' },
    ])
    vi.spyOn(workflowsApi, 'fetchWorkflowInstances').mockResolvedValue([
      { public_id: 'inst-1' },
    ])

    renderBinding(
      <WorkflowBindingRenderer
        component={{
          public_id: '1',
          component_key: 'queue',
          name: 'Queue',
          component_type: 'workflow_queue',
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('workflow-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByText('Approval Flow')).toBeInTheDocument()
  })
})
