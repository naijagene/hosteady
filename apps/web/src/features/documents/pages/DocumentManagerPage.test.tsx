import { describe, expect, it } from 'vitest'
import { render, screen } from '@testing-library/react'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { DocumentManagerPage } from '@/features/documents/pages/DocumentManagerPage'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'
import * as documentsApi from '@/api/endpoints/documents'
import { vi } from 'vitest'

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

describe('DocumentManagerPage', () => {
  it('renders document manager shell', async () => {
    vi.spyOn(documentsApi, 'fetchDocuments').mockResolvedValue({
      items: [],
      page: 1,
      per_page: 25,
      total: 0,
      has_more: false,
    })

    useAuthStore.getState().setHydratedRuntime(runtime)
    const client = new QueryClient({ defaultOptions: { queries: { retry: false } } })

    render(
      <QueryClientProvider client={client}>
        <HydratedRuntimeProvider>
          <DocumentManagerPage />
        </HydratedRuntimeProvider>
      </QueryClientProvider>,
    )

    expect(await screen.findByTestId('document-loading-state')).toBeInTheDocument()
  })
})
