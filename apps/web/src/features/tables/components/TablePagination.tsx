import type { TablePagination as TablePaginationMeta } from '@/api/types/tables'

interface TablePaginationProps {
  pagination: TablePaginationMeta
  onPageChange: (page: number) => void
  onPerPageChange: (perPage: number) => void
}

export function TablePagination({
  pagination,
  onPageChange,
  onPerPageChange,
}: TablePaginationProps) {
  return (
    <div
      className="flex flex-wrap items-center justify-between gap-3 border-t border-border px-4 py-3 text-xs text-muted-foreground"
      data-testid="table-pagination"
    >
      <p>
        Page {pagination.page} of {pagination.last_page} · {pagination.total} records
      </p>
      <div className="flex items-center gap-2">
        <label htmlFor="table-per-page" className="sr-only">
          Rows per page
        </label>
        <select
          id="table-per-page"
          className="rounded-md border border-border bg-background px-2 py-1 text-foreground"
          value={pagination.per_page}
          onChange={(event) => onPerPageChange(Number(event.target.value))}
        >
          {[10, 25, 50, 100].map((size) => (
            <option key={size} value={size}>
              {size} / page
            </option>
          ))}
        </select>
        <button
          type="button"
          className="rounded-md border border-border px-2 py-1 disabled:opacity-50"
          disabled={pagination.page <= 1}
          onClick={() => onPageChange(pagination.page - 1)}
        >
          Previous
        </button>
        <button
          type="button"
          className="rounded-md border border-border px-2 py-1 disabled:opacity-50"
          disabled={pagination.page >= pagination.last_page}
          onClick={() => onPageChange(pagination.page + 1)}
        >
          Next
        </button>
      </div>
    </div>
  )
}
