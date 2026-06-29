interface DeleteConfirmationDialogProps {
  open: boolean
  title?: string
  message?: string
  onConfirm: () => void
  onCancel: () => void
}

export function DeleteConfirmationDialog({
  open,
  title = 'Delete record',
  message = 'Delete confirmation placeholder. No records are deleted yet.',
  onConfirm,
  onCancel,
}: DeleteConfirmationDialogProps) {
  if (!open) {
    return null
  }

  return (
    <div
      className="fixed inset-0 z-50 flex items-center justify-center bg-background/80 p-4"
      role="dialog"
      aria-modal="true"
      data-testid="delete-confirmation-dialog"
    >
      <div className="w-full max-w-md rounded-lg border border-border bg-card p-4 shadow-lg">
        <h3 className="text-sm font-semibold text-foreground">{title}</h3>
        <p className="mt-2 text-sm text-muted-foreground">{message}</p>
        <div className="mt-4 flex justify-end gap-2">
          <button
            type="button"
            className="rounded-md border border-border px-3 py-1 text-sm"
            onClick={onCancel}
          >
            Cancel
          </button>
          <button
            type="button"
            className="rounded-md bg-destructive px-3 py-1 text-sm text-destructive-foreground"
            onClick={onConfirm}
          >
            Delete (placeholder)
          </button>
        </div>
      </div>
    </div>
  )
}
