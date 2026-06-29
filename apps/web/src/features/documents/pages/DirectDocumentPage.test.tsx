import { beforeEach, describe, expect, it, vi } from 'vitest'
import { render, screen, waitFor } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { DirectDocumentPage } from '@/features/documents/pages/DirectDocumentPage'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as documentsApi from '@/api/endpoints/documents'

vi.mock('@tanstack/react-router', async () => {
  const actual = await vi.importActual('@tanstack/react-router')
  return {
    ...actual,
    useParams: () => ({ documentPublicId: 'doc-1' }),
  }
})

const runtime: HydratedRuntimeBundle = {
  tenantContext: null,
  workspaceRuntime: null,
  themeRuntime: null,
  personalizationRuntime: null,
  navigationMenus: [],
  permissions: ['documents.read'],
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

describe('DirectDocumentPage', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    useAuthStore.getState().setHydratedRuntime(runtime)
  })

  it('loads document manager with initial document id', async () => {
    vi.spyOn(documentsApi, 'fetchDocuments').mockResolvedValue({
      items: [{ public_id: 'doc-1', title: 'Policy Document' }],
      page: 1,
      per_page: 25,
      total: 1,
      has_more: false,
    })

    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })
    render(
      <QueryClientProvider client={client}>
        <HydratedRuntimeProvider>
          <DirectDocumentPage />
        </HydratedRuntimeProvider>
      </QueryClientProvider>,
    )

    await waitFor(() => {
      expect(screen.getByTestId('document-manager')).toBeInTheDocument()
    })
  })
})
