interface DocumentActionMenuProps {
  canUpload?: boolean
  canDelete?: boolean
  canDownload?: boolean
  onUpload?: () => void
  onRefresh?: () => void
  onDelete?: () => void
  onDownload?: () => void
  message?: string | null
}

export function DocumentActionMenu({
  canUpload = true,
  canDelete = true,
  canDownload = true,
  onUpload,
  onRefresh,
  onDelete,
  onDownload,
  message,
}: DocumentActionMenuProps) {
  return (
    <div className="flex flex-wrap gap-2" data-testid="document-action-menu" aria-label="Document actions">
      <button
        type="button"
        className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
        aria-label="Refresh documents"
        onClick={onRefresh}
      >
        Refresh
      </button>
      {canUpload ? (
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
          aria-label="Upload document"
          onClick={onUpload}
        >
          Upload
        </button>
      ) : null}
      {canDownload ? (
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
          aria-label="Download document"
          onClick={onDownload}
        >
          Download
        </button>
      ) : null}
      {canDelete ? (
        <button
          type="button"
          className="rounded-md border border-destructive/40 px-3 py-1 text-xs text-destructive hover:bg-destructive/5"
          aria-label="Delete document"
          onClick={onDelete}
        >
          Delete
        </button>
      ) : null}
      {message ? (
        <p className="w-full text-xs text-muted-foreground" role="status">
          {message}
        </p>
      ) : null}
    </div>
  )
}
