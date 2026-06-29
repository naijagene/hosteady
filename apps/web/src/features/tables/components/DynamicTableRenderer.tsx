import { useCallback, useMemo, useState } from 'react'
import { extractTablePagination } from '@/api/types/tables'
import type { TableAction, TableBindingContext, TableDefinition } from '@/api/types/tables'
import { getRowSelectionKey } from '../core/table-selection'
import { toggleColumnSort } from '../core/table-sorts'
import { useDynamicTable } from '../hooks/useDynamicTable'
import { useTableColumns } from '../hooks/useTableColumns'
import { useTableFilters } from '../hooks/useTableFilters'
import { useTableQuery } from '../hooks/useTableQuery'
import { useTableSelection } from '../hooks/useTableSelection'
import { DeleteConfirmationDialog } from './DeleteConfirmationDialog'
import { RecordDrawer } from './RecordDrawer'
import { TableActionBar } from './TableActionBar'
import { TableCellRenderer } from './TableCellRenderer'
import { TableColumnHeader } from './TableColumnHeader'
import { TableColumnVisibilityMenu } from './TableColumnVisibilityMenu'
import { TableEmptyState } from './TableEmptyState'
import { TableErrorState } from './TableErrorState'
import { TableFilterPanel } from './TableFilterPanel'
import { TableLoadingState } from './TableLoadingState'
import { TablePagination } from './TablePagination'
import { TableRowActions } from './TableRowActions'
import { TableSearchBox } from './TableSearchBox'
import { TableToolbar } from './TableToolbar'
import { TableViewSelector } from './TableViewSelector'

interface DynamicTableRendererProps {
  definition: TableDefinition
  binding?: TableBindingContext
  queryEnabled?: boolean
}

type DrawerState =
  | { open: false }
  | {
      open: true
      mode: 'create' | 'edit'
      title: string
      moduleKey: string
      formKey: string
    }

export function DynamicTableRenderer({
  definition,
  binding,
  queryEnabled = true,
}: DynamicTableRendererProps) {
  const { model, initialQueryState, toolbarActions, rowActions } = useDynamicTable({
    definition,
    binding,
  })

  const [queryState, setQueryState] = useState(initialQueryState)
  const { filters, setFilter, clearFilter } = useTableFilters(initialQueryState.filters)
  const {
    visibleColumns,
    visibleColumnKeys,
    hiddenColumnKeys,
    selectedView,
    setSelectedView,
    toggleColumn,
  } = useTableColumns(model)

  const mergedQueryState = useMemo(
    () => ({
      ...queryState,
      filters,
      selectedView,
    }),
    [queryState, filters, selectedView],
  )

  const tableQuery = useTableQuery({
    moduleKey: definition.module_key,
    tableKey: definition.table_key,
    queryState: mergedQueryState,
    visibleColumnKeys,
    binding,
    enabled: queryEnabled,
  })

  const rows = tableQuery.data?.rows ?? []
  const pagination = extractTablePagination(tableQuery.data)
  const selection = useTableSelection(rows)

  const [drawer, setDrawer] = useState<DrawerState>({ open: false })
  const [deleteOpen, setDeleteOpen] = useState(false)
  const [unsupportedAction, setUnsupportedAction] = useState<string | null>(null)

  const openCreateDrawer = useCallback(() => {
    if (!model.createForm) {
      return
    }

    setDrawer({
      open: true,
      mode: 'create',
      title: `Create ${definition.name}`,
      moduleKey: model.createForm.module_key,
      formKey: model.createForm.form_key,
    })
  }, [definition.name, model.createForm])

  const openEditDrawer = useCallback(() => {
    if (!model.editForm) {
      return
    }

    setDrawer({
      open: true,
      mode: 'edit',
      title: `Edit ${definition.name}`,
      moduleKey: model.editForm.module_key,
      formKey: model.editForm.form_key,
    })
  }, [definition.name, model.editForm])

  const handleToolbarAction = useCallback(
    (action: TableAction) => {
      const type = action.action_type.toLowerCase()

      switch (type) {
        case 'create':
          openCreateDrawer()
          break
        case 'refresh':
          tableQuery.refresh()
          break
        case 'export':
          setUnsupportedAction('Export is not implemented yet.')
          break
        case 'custom':
          setUnsupportedAction(`${action.label} is not supported yet.`)
          break
        default:
          setUnsupportedAction(`${action.label} is not supported yet.`)
      }
    },
    [openCreateDrawer, tableQuery],
  )

  const handleRowAction = useCallback(
    (action: TableAction) => {
      const type = action.action_type.toLowerCase()

      switch (type) {
        case 'view':
          setUnsupportedAction('View record is not implemented yet.')
          break
        case 'edit':
          openEditDrawer()
          break
        case 'delete':
          setDeleteOpen(true)
          break
        default:
          setUnsupportedAction(`${action.label} is not supported yet.`)
      }
    },
    [openEditDrawer],
  )

  const handleFormSuccess = useCallback(() => {
    if (binding?.refresh_on_form_success !== false) {
      tableQuery.invalidate()
    }
  }, [binding?.refresh_on_form_success, tableQuery])

  const showLoading = tableQuery.isLoading && rows.length === 0
  const showError = tableQuery.isError && !tableQuery.data
  const showEmpty = !showLoading && !showError && rows.length === 0

  return (
    <section
      className="overflow-hidden rounded-lg border border-border bg-card"
      data-testid="dynamic-table-renderer"
    >
      <TableToolbar
        title={definition.name}
        description={definition.description}
        search={
          <TableSearchBox
            value={queryState.search}
            loading={tableQuery.isFetching}
            onChange={(search) =>
              setQueryState((current) => ({ ...current, page: 1, search }))
            }
          />
        }
        filters={
          <TableFilterPanel
            columns={visibleColumns}
            filters={filters}
            onApplyFilter={setFilter}
            onClearFilter={clearFilter}
          />
        }
        views={
          <TableViewSelector
            views={model.views}
            selectedView={selectedView}
            onChange={setSelectedView}
          />
        }
        columnsMenu={
          <TableColumnVisibilityMenu
            columns={model.columns}
            hiddenColumnKeys={hiddenColumnKeys}
            onToggle={toggleColumn}
          />
        }
        actions={
          <TableActionBar
            actions={toolbarActions}
            selectedCount={selection.selectedCount}
            onAction={handleToolbarAction}
          />
        }
      />

      {unsupportedAction ? (
        <p className="px-4 py-2 text-xs text-muted-foreground" role="status">
          {unsupportedAction}
        </p>
      ) : null}

      {showLoading ? <TableLoadingState /> : null}
      {showError ? (
        <TableErrorState
          message={tableQuery.errorInfo?.message ?? 'Unable to load table data.'}
        />
      ) : null}

      {!showLoading && !showError ? (
        <div className="overflow-x-auto">
          <table className="min-w-full text-left">
            <thead className="bg-muted/40">
              <tr>
                <th scope="col" className="px-4 py-2">
                  <span className="sr-only">Select row</span>
                  <input
                    type="checkbox"
                    aria-label="Select all visible rows"
                    checked={selection.isAllSelected}
                    onChange={(event) => selection.toggleAll(event.target.checked)}
                  />
                </th>
                {visibleColumns.map((column) => (
                  <TableColumnHeader
                    key={column.column_key}
                    column={column}
                    sorts={mergedQueryState.sorts}
                    onSort={(columnKey) =>
                      setQueryState((current) => ({
                        ...current,
                        page: 1,
                        sorts: toggleColumnSort(current.sorts, columnKey),
                      }))
                    }
                  />
                ))}
                {rowActions.length > 0 ? (
                  <th scope="col" className="px-4 py-2 text-xs text-muted-foreground">
                    Actions
                  </th>
                ) : null}
              </tr>
            </thead>
            <tbody>
              {showEmpty ? (
                <tr>
                  <td colSpan={visibleColumns.length + 2}>
                    <TableEmptyState />
                  </td>
                </tr>
              ) : (
                rows.map((row, index) => {
                  const rowKey = getRowSelectionKey(row, index)

                  return (
                    <tr key={rowKey} className="border-t border-border">
                      <td className="px-4 py-2">
                        <input
                          type="checkbox"
                          aria-label={`Select row ${rowKey}`}
                          checked={selection.isRowSelected(rowKey)}
                          onChange={() => selection.toggleRow(rowKey)}
                        />
                      </td>
                      {visibleColumns.map((column) => (
                        <TableCellRenderer key={column.column_key} column={column} row={row} />
                      ))}
                      {rowActions.length > 0 ? (
                        <td className="px-4 py-2">
                          <TableRowActions actions={rowActions} onAction={handleRowAction} />
                        </td>
                      ) : null}
                    </tr>
                  )
                })
              )}
            </tbody>
          </table>
        </div>
      ) : null}

      {!showLoading && !showError && rows.length > 0 ? (
        <TablePagination
          pagination={pagination}
          onPageChange={(page) => setQueryState((current) => ({ ...current, page }))}
          onPerPageChange={(perPage) =>
            setQueryState((current) => ({ ...current, page: 1, perPage }))
          }
        />
      ) : null}

      <RecordDrawer
        open={drawer.open}
        title={drawer.open ? drawer.title : ''}
        moduleKey={drawer.open ? drawer.moduleKey : ''}
        formKey={drawer.open ? drawer.formKey : ''}
        mode={drawer.open ? drawer.mode : 'create'}
        onClose={() => setDrawer({ open: false })}
        onSuccess={handleFormSuccess}
      />

      <DeleteConfirmationDialog
        open={deleteOpen}
        onCancel={() => setDeleteOpen(false)}
        onConfirm={() => setDeleteOpen(false)}
      />
    </section>
  )
}
