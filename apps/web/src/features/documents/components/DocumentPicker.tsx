import { useEffect, useState } from 'react'
import type { DocumentPickerResult, DocumentReference } from '@/api/types/documents'
import { DocumentManager } from './DocumentManager'

interface DocumentPickerProps {
  open: boolean
  multiple?: boolean
  onClose: () => void
  onConfirm: (result: DocumentPickerResult) => void
}

export function DocumentPicker({ open, multiple = false, onClose, onConfirm }: DocumentPickerProps) {
  const [selection, setSelection] = useState<DocumentReference[]>([])

  useEffect(() => {
    if (!open) {
      return undefined
    }

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        onClose()
      }
    }

    window.addEventListener('keydown', onKeyDown)
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [onClose, open])

  if (!open) {
    return null
  }

  const handleClose = () => {
    setSelection([])
    onClose()
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" role="presentation">
      <div
        className="max-h-[90vh] w-full max-w-5xl overflow-hidden rounded-lg border border-border bg-card shadow-xl"
        role="dialog"
        aria-modal="true"
        aria-label="Document picker"
        data-testid="document-picker"
      >
        <div className="flex items-center justify-between border-b border-border px-4 py-3">
          <h3 className="text-sm font-semibold text-foreground">Select document{multiple ? 's' : ''}</h3>
          <button type="button" className="text-xs text-muted-foreground hover:text-foreground" aria-label="Close document picker" onClick={handleClose}>
            Close
          </button>
        </div>
        <div className="max-h-[calc(90vh-8rem)] overflow-y-auto">
          <DocumentManager
            title="Browse documents"
            binding={{
              mode: 'picker',
              query_enabled: true,
              search_enabled: true,
              upload_enabled: false,
              selection_enabled: true,
              detail_enabled: true,
              per_page: 10,
            }}
            onSelectionChange={setSelection}
          />
        </div>
        <div className="flex justify-end gap-2 border-t border-border px-4 py-3">
          <button type="button" className="rounded-md border border-border px-3 py-1 text-xs" onClick={handleClose}>
            Cancel
          </button>
          <button
            type="button"
            className="rounded-md border border-border px-3 py-1 text-xs"
            aria-label="Confirm document selection"
            onClick={() => {
              onConfirm({
                documents: multiple ? selection : selection.slice(-1),
                selection_mode: multiple ? 'multiple' : 'single',
              })
              setSelection([])
            }}
          >
            Confirm
          </button>
        </div>
      </div>
    </div>
  )
}
