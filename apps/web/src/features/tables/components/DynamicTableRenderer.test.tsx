import { beforeEach, describe, expect, it, vi } from 'vitest'
import { fireEvent, render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import type { TableDefinition } from '@/api/types/tables'
import * as formsApi from '@/api/endpoints/forms'
import * as tablesApi from '@/api/endpoints/tables'
import { DynamicTableRenderer } from '@/features/tables/components/DynamicTableRenderer'
import { HydratedRuntimeProvider } from '@/features/runtime/HydratedRuntimeProvider'
import { useAuthStore } from '@/stores/auth-store'
import type { HydratedRuntimeBundle } from '@/api/types/runtime'

const definition: TableDefinition = {
  module_key: 'platform',
  table_key: 'users',
  name: 'Users',
  description: 'Platform users',
  columns: [
    { column_key: 'name', label: 'Name', column_type: 'text', sortable: true, filterable: true },
    { column_key: 'status', label: 'Status', column_type: 'status' },
  ],
  actions: [
    { action_key: 'create', label: 'Create', action_type: 'create' },
    { action_key: 'edit', label: 'Edit', action_type: 'edit' },
    { action_key: 'delete', label: 'Delete', action_type: 'delete' },
  ],
  create_form: { module_key: 'platform', form_key: 'user-create' },
  edit_form: { module_key: 'platform', form_key: 'user-edit' },
}

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

function renderTable(ui: React.ReactNode, client = new QueryClient({ defaultOptions: { queries: { retry: false } } })) {
  useAuthStore.getState().setHydratedRuntime(runtime)
  return {
    client,
    ...render(
      <QueryClientProvider client={client}>
        <HydratedRuntimeProvider>{ui}</HydratedRuntimeProvider>
      </QueryClientProvider>,
    ),
  }
}

describe('DynamicTableRenderer', () => {
  beforeEach(() => {
    vi.restoreAllMocks()
    vi.spyOn(tablesApi, 'queryTable').mockResolvedValue({
      rows: [{ public_id: '1', values: { name: 'Ada', status: 'Active' } }],
      total: 1,
      page: 1,
      per_page: 25,
      last_page: 1,
    })
  })

  it('renders table rows from backend query', async () => {
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => {
      expect(screen.getByTestId('dynamic-table-renderer')).toBeInTheDocument()
      expect(screen.getByText('Ada')).toBeInTheDocument()
    })
    expect(screen.getByText('Active')).toBeInTheDocument()
  })

  it('shows loading state before data arrives', () => {
    vi.spyOn(tablesApi, 'queryTable').mockReturnValue(new Promise(() => undefined))
    renderTable(<DynamicTableRenderer definition={definition} />)
    expect(screen.getByTestId('table-loading-state')).toBeInTheDocument()
  })

  it('shows error state when query fails', async () => {
    vi.spyOn(tablesApi, 'queryTable').mockRejectedValue(new Error('Query failed'))
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => {
      expect(screen.getByTestId('table-error-state')).toHaveTextContent('Query failed')
    })
  })

  it('shows empty state when no rows returned', async () => {
    vi.spyOn(tablesApi, 'queryTable').mockResolvedValue({ rows: [], total: 0, page: 1, per_page: 25, last_page: 1 })
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => {
      expect(screen.getByTestId('table-empty-state')).toBeInTheDocument()
    })
  })

  it('opens create drawer with dynamic form', async () => {
    const user = userEvent.setup()
    vi.spyOn(formsApi, 'fetchFormDefinition').mockResolvedValue({
      module_key: 'platform',
      form_key: 'user-create',
      name: 'Create User',
      sections: [{ section_key: 'main', label: 'Main', fields: ['name'] }],
      fields: [{ field_key: 'name', label: 'Name', field_type: 'text', section_key: 'main' }],
    })

    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => expect(screen.getByText('Ada')).toBeInTheDocument())
    await user.click(screen.getAllByRole('button', { name: 'Create' })[0]!)
    await waitFor(() => {
      expect(screen.getByTestId('record-drawer')).toBeInTheDocument()
      expect(screen.getByTestId('dynamic-form-renderer')).toBeInTheDocument()
    })
  })

  it('opens delete confirmation placeholder', async () => {
    const user = userEvent.setup()
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => expect(screen.getByText('Ada')).toBeInTheDocument())
    await user.click(screen.getByRole('button', { name: 'Delete' }))
    expect(screen.getByTestId('delete-confirmation-dialog')).toBeInTheDocument()
  })

  it('selects rows with accessible labels', async () => {
    const user = userEvent.setup()
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => expect(screen.getByText('Ada')).toBeInTheDocument())
    await user.click(screen.getByRole('checkbox', { name: 'Select row 1' }))
    expect(screen.getByText('1 selected')).toBeInTheDocument()
  })

  it('invalidates query after refresh action', async () => {
    const user = userEvent.setup()
    const queryTable = vi.spyOn(tablesApi, 'queryTable')
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => expect(screen.getByText('Ada')).toBeInTheDocument())
    queryTable.mockClear()
    await user.click(screen.getByRole('button', { name: 'Refresh' }))
    await waitFor(() => {
      expect(queryTable).toHaveBeenCalled()
    })
  })

  it('sends query payload when searching', async () => {
    const queryTable = vi.spyOn(tablesApi, 'queryTable')
    renderTable(<DynamicTableRenderer definition={definition} />)
    await waitFor(() => expect(screen.getByText('Ada')).toBeInTheDocument())
    queryTable.mockClear()
    fireEvent.change(screen.getByLabelText('Search records'), { target: { value: 'ada' } })
    await waitFor(
      () => {
        expect(queryTable).toHaveBeenCalled()
        const payload = queryTable.mock.calls.at(-1)?.[2]
        expect(payload?.search).toBe('ada')
      },
      { timeout: 1000 },
    )
  })
})
