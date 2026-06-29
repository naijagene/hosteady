import type { ReactNode } from 'react'

interface TableToolbarProps {
  title: string
  description?: string | null
  search?: ReactNode
  filters?: ReactNode
  actions?: ReactNode
  views?: ReactNode
  columnsMenu?: ReactNode
}

export function TableToolbar({
  title,
  description,
  search,
  filters,
  actions,
  views,
  columnsMenu,
}: TableToolbarProps) {
  return (
    <div className="space-y-3 border-b border-border px-4 py-3" data-testid="table-toolbar">
      <div className="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 className="text-sm font-semibold text-foreground">{title}</h2>
          {description ? (
            <p className="text-xs text-muted-foreground">{description}</p>
          ) : null}
        </div>
        <div className="flex flex-wrap items-center gap-2">
          {views}
          {columnsMenu}
          {actions}
        </div>
      </div>
      <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        {search}
        {filters}
      </div>
    </div>
  )
}
