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
import type { UiComponent } from '@/api/types/ui'

function renderBinding(ui: React.ReactNode) {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })

  return render(
    <QueryClientProvider client={client}>
      <RendererContextProvider moduleKey="platform">{ui}</RendererContextProvider>
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
    })

    renderBinding(<FormBindingRenderer component={formComponent} />)

    await waitFor(() => {
      expect(screen.getByTestId('form-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByText('Name')).toBeInTheDocument()
  })

  it('TableBindingRenderer loads table metadata', async () => {
    vi.spyOn(tablesApi, 'fetchTableDefinition').mockResolvedValue({
      module_key: 'platform',
      table_key: 'users',
      name: 'Users',
      columns: [{ column_key: 'email', label: 'Email' }],
    })
    vi.spyOn(tablesApi, 'queryTable').mockResolvedValue({ rows: [] })

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
    expect(screen.getByText('Email')).toBeInTheDocument()
  })

  it('DashboardBindingRenderer renders widget placeholders', async () => {
    vi.spyOn(dashboardsApi, 'fetchDashboardRender').mockResolvedValue({
      dashboard: {
        module_key: 'platform',
        dashboard_key: 'overview',
        name: 'Overview',
      },
      widgets: [{ widget_key: 'kpi', label: 'KPI' }],
    })

    renderBinding(
      <DashboardBindingRenderer
        component={{
          ...formComponent,
          component_type: 'dashboard',
          binding_type: 'dashboard',
          binding_config: { module_key: 'platform', resource_key: 'overview' },
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('dashboard-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByText('KPI')).toBeInTheDocument()
  })

  it('ReportBindingRenderer renders section placeholders', async () => {
    vi.spyOn(reportsApi, 'fetchReportRender').mockResolvedValue({
      report: {
        module_key: 'platform',
        report_key: 'summary',
        name: 'Summary Report',
      },
      sections: [{ title: 'Section A' }],
    })

    renderBinding(
      <ReportBindingRenderer
        component={{
          ...formComponent,
          component_type: 'report',
          binding_type: 'report',
          binding_config: { module_key: 'platform', resource_key: 'summary' },
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('report-binding-renderer')).toBeInTheDocument()
    })
    expect(screen.getByText('Summary Report')).toBeInTheDocument()
  })

  it('DocumentBindingRenderer lists document placeholders', async () => {
    vi.spyOn(documentsApi, 'fetchDocuments').mockResolvedValue([
      { public_id: 'doc-1', title: 'Policy Document' },
    ])

    renderBinding(
      <DocumentBindingRenderer
        component={{
          public_id: '1',
          component_key: 'docs',
          name: 'Documents',
          component_type: 'document_list',
        }}
      />,
    )

    await waitFor(() => {
      expect(screen.getByTestId('document-binding-renderer')).toBeInTheDocument()
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
