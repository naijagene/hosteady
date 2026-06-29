import { beforeEach, describe, expect, it, vi } from 'vitest'
import { act, fireEvent, render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { DeleteConfirmationDialog } from '@/features/tables/components/DeleteConfirmationDialog'
import { TableColumnHeader } from '@/features/tables/components/TableColumnHeader'
import { TableColumnVisibilityMenu } from '@/features/tables/components/TableColumnVisibilityMenu'
import { TableEmptyState } from '@/features/tables/components/TableEmptyState'
import { TableErrorState } from '@/features/tables/components/TableErrorState'
import { TableLoadingState } from '@/features/tables/components/TableLoadingState'
import { TablePagination } from '@/features/tables/components/TablePagination'
import { TableSearchBox } from '@/features/tables/components/TableSearchBox'
import { TableViewSelector } from '@/features/tables/components/TableViewSelector'

describe('table UI components', () => {
  beforeEach(() => {
    vi.useRealTimers()
  })

  it('renders loading state with status role', () => {
    render(<TableLoadingState />)
    expect(screen.getByRole('status')).toHaveTextContent('Loading table data')
  })

  it('renders error state with alert role', () => {
    render(<TableErrorState message="Query failed" />)
    expect(screen.getByRole('alert')).toHaveTextContent('Query failed')
  })

  it('renders empty state', () => {
    render(<TableEmptyState message="Nothing here" />)
    expect(screen.getByTestId('table-empty-state')).toHaveTextContent('Nothing here')
  })

  it('paginates with next and previous controls', async () => {
    const user = userEvent.setup()
    const onPageChange = vi.fn()
    render(
      <TablePagination
        pagination={{ page: 2, per_page: 25, total: 100, last_page: 4 }}
        onPageChange={onPageChange}
        onPerPageChange={vi.fn()}
      />,
    )
    await user.click(screen.getByRole('button', { name: 'Previous' }))
    expect(onPageChange).toHaveBeenCalledWith(1)
  })

  it('changes page size', async () => {
    const user = userEvent.setup()
    const onPerPageChange = vi.fn()
    render(
      <TablePagination
        pagination={{ page: 1, per_page: 25, total: 10, last_page: 1 }}
        onPageChange={vi.fn()}
        onPerPageChange={onPerPageChange}
      />,
    )
    await user.selectOptions(screen.getByLabelText('Rows per page'), '50')
    expect(onPerPageChange).toHaveBeenCalledWith(50)
  })

  it('debounces search input', () => {
    vi.useFakeTimers()
    const onChange = vi.fn()
    render(<TableSearchBox value="" onChange={onChange} />)
    fireEvent.change(screen.getByLabelText('Search records'), { target: { value: 'ada' } })
    act(() => {
      vi.advanceTimersByTime(300)
    })
    expect(onChange).toHaveBeenCalledWith('ada')
    vi.useRealTimers()
  })

  it('clears search input', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    render(<TableSearchBox value="ada" onChange={onChange} />)
    await user.click(screen.getByRole('button', { name: 'Clear' }))
    expect(onChange).toHaveBeenCalledWith('')
  })

  it('shows search loading indicator', () => {
    render(<TableSearchBox value="" onChange={vi.fn()} loading />)
    expect(screen.getByText('Searching…')).toBeInTheDocument()
  })

  it('renders sortable column header with aria-sort', async () => {
    const user = userEvent.setup()
    const onSort = vi.fn()
    render(
      <table>
        <thead>
          <tr>
            <TableColumnHeader
              column={{
                column_key: 'name',
                label: 'Name',
                column_type: 'text',
                visibleInView: true,
                sortable: true,
              }}
              sorts={[]}
              onSort={onSort}
            />
          </tr>
        </thead>
      </table>,
    )
    expect(screen.getByRole('columnheader')).toHaveAttribute('aria-sort', 'none')
    await user.click(screen.getByRole('button', { name: /Name/ }))
    expect(onSort).toHaveBeenCalledWith('name')
  })

  it('renders view selector', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    render(
      <TableViewSelector
        views={[
          { view_key: 'all', label: 'All' },
          { view_key: 'active', label: 'Active' },
        ]}
        selectedView="all"
        onChange={onChange}
      />,
    )
    await user.selectOptions(screen.getByRole('combobox'), 'active')
    expect(onChange).toHaveBeenCalledWith('active')
  })

  it('toggles column visibility', async () => {
    const user = userEvent.setup()
    const onToggle = vi.fn()
    render(
      <TableColumnVisibilityMenu
        columns={[
          { column_key: 'name', label: 'Name', column_type: 'text', visibleInView: true },
        ]}
        hiddenColumnKeys={new Set()}
        onToggle={onToggle}
      />,
    )
    await user.click(screen.getByRole('checkbox'))
    expect(onToggle).toHaveBeenCalledWith('name')
  })

  it('shows delete confirmation placeholder', async () => {
    const user = userEvent.setup()
    const onConfirm = vi.fn()
    render(
      <DeleteConfirmationDialog
        open
        onConfirm={onConfirm}
        onCancel={vi.fn()}
      />,
    )
    expect(screen.getByRole('dialog')).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /Delete \(placeholder\)/ }))
    expect(onConfirm).toHaveBeenCalled()
  })
})
