import { DocumentActionMenu } from './DocumentActionMenu'
import { DocumentSearchBox } from './DocumentSearchBox'
import { DocumentViewToggle } from './DocumentViewToggle'

interface DocumentToolbarProps {
  title?: string
  viewMode: 'list' | 'grid'
  search: string
  searchEnabled?: boolean
  onSearchChange: (value: string) => void
  onViewModeChange: (mode: 'list' | 'grid') => void
  canUpload?: boolean
  canDelete?: boolean
  canDownload?: boolean
  onUpload?: () => void
  onRefresh?: () => void
  onDelete?: () => void
  onDownload?: () => void
  actionMessage?: string | null
  compact?: boolean
}

export function DocumentToolbar({
  title = 'Documents',
  viewMode,
  search,
  searchEnabled = true,
  onSearchChange,
  onViewModeChange,
  canUpload,
  canDelete,
  canDownload,
  onUpload,
  onRefresh,
  onDelete,
  onDownload,
  actionMessage,
  compact = false,
}: DocumentToolbarProps) {
  return (
    <header className="space-y-3 border-b border-border px-4 py-3" data-testid="document-toolbar" aria-label="Document toolbar">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-sm font-semibold text-foreground">{title}</h2>
        {!compact ? <DocumentViewToggle viewMode={viewMode} onChange={onViewModeChange} /> : null}
      </div>
      <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        {searchEnabled ? <DocumentSearchBox value={search} onChange={onSearchChange} /> : null}
        <DocumentActionMenu
          canUpload={canUpload}
          canDelete={canDelete}
          canDownload={canDownload}
          onUpload={onUpload}
          onRefresh={onRefresh}
          onDelete={onDelete}
          onDownload={onDownload}
          message={actionMessage}
        />
      </div>
    </header>
  )
}
