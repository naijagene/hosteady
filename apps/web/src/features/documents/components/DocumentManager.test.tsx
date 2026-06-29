import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import * as documentsApi from '@/api/endpoints/documents'
import { DocumentManager } from '@/features/documents/components/DocumentManager'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['documents.read', 'documents.upload', 'documents.delete'],
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

function renderManager() {
  const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  useAuthStore.getState().setHydratedRuntime(runtime)

  return render(
    <QueryClientProvider client={client}>
      <HydratedRuntimeProvider>
        <DocumentManager binding={{ mode: 'list', query_enabled: true, search_enabled: true, upload_enabled: true }} />
      </HydratedRuntimeProvider>
    </QueryClientProvider>,
  )
}

describe('DocumentManager', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
  })

  it('renders list of documents', async () => {
    vi.spyOn(documentsApi, 'fetchDocuments').mockResolvedValue({
      items: [
        {
          public_id: 'doc-1',
          title: 'Policy Document',
          mime_type: 'application/pdf',
          updated_at: '2024-01-01',
        },
      ],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
    })

    renderManager()

    await waitFor(() => {
      expect(screen.getByTestId('document-manager')).toBeInTheDocument()
    })
    expect(screen.getByText('Policy Document')).toBeInTheDocument()
  })

  it('renders empty state', async () => {
    vi.spyOn(documentsApi, 'fetchDocuments').mockResolvedValue({
      items: [],
      page: 1,
      per_page: 25,
      total: 0,
      has_more: false,
    })

    renderManager()

    await waitFor(() => {
      expect(screen.getByTestId('document-empty-state')).toBeInTheDocument()
    })
  })
})
