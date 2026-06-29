import { describe, expect, it, vi } from 'vitest'
import * as uiApi from '@/api/endpoints/ui'
import * as formsApi from '@/api/endpoints/forms'
import * as tablesApi from '@/api/endpoints/tables'
import * as dashboardsApi from '@/api/endpoints/dashboards'
import * as reportsApi from '@/api/endpoints/reports'
import * as documentsApi from '@/api/endpoints/documents'
import * as workflowsApi from '@/api/endpoints/workflows'
import { apiClient } from '@/api/client'

vi.mock('@/api/client', () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
  },
}))

describe('metadata API endpoints', () => {
  it('fetchUiPageRender unwraps data envelope', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: { page: { page_key: 'home', name: 'Home' } } },
    })

    const payload = await uiApi.fetchUiPageRender('platform', 'home')
    expect(payload.page.page_key).toBe('home')
    expect(apiClient.get).toHaveBeenCalledWith('tenant/ui/pages/platform/home/render')
  })

  it('fetchUiPages normalizes list payload', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: [{ page_key: 'home', name: 'Home', module_key: 'platform' }] },
    })

    const pages = await uiApi.fetchUiPages()
    expect(pages[0].page_key).toBe('home')
  })

  it('fetchFormDefinition calls correct route', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: { module_key: 'platform', form_key: 'profile', name: 'Profile' } },
    })

    const form = await formsApi.fetchFormDefinition('platform', 'profile')
    expect(form.form_key).toBe('profile')
  })

  it('queryTable posts to query endpoint', async () => {
    vi.mocked(apiClient.post).mockResolvedValue({
      data: { data: { rows: [], total: 0 } },
    })

    const result = await tablesApi.queryTable('platform', 'users')
    expect(result.total).toBe(0)
  })

  it('fetchDashboardRender calls render endpoint', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: {
        data: {
          dashboard: { module_key: 'platform', dashboard_key: 'overview', name: 'Overview' },
          widgets: [],
        },
      },
    })

    const payload = await dashboardsApi.fetchDashboardRender('platform', 'overview')
    expect(payload.dashboard.dashboard_key).toBe('overview')
  })

  it('fetchReportRender calls render endpoint', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: {
        data: {
          report: { module_key: 'platform', report_key: 'summary', name: 'Summary' },
          sections: [],
        },
      },
    })

    const payload = await reportsApi.fetchReportRender('platform', 'summary')
    expect(payload.report.report_key).toBe('summary')
  })

  it('fetchDocuments unwraps list', async () => {
    vi.mocked(apiClient.get).mockResolvedValue({
      data: { data: [{ public_id: 'doc-1', title: 'Doc' }] },
    })

    const docs = await documentsApi.fetchDocuments()
    expect(docs[0].title).toBe('Doc')
  })

  it('fetchWorkflowDefinitions and instances', async () => {
    vi.mocked(apiClient.get)
      .mockResolvedValueOnce({
        data: { data: [{ public_id: 'wf-1', name: 'Flow' }] },
      })
      .mockResolvedValueOnce({
        data: { data: [{ public_id: 'inst-1' }] },
      })

    const definitions = await workflowsApi.fetchWorkflowDefinitions()
    const instances = await workflowsApi.fetchWorkflowInstances()

    expect(definitions[0].name).toBe('Flow')
    expect(instances[0].public_id).toBe('inst-1')
  })
})
