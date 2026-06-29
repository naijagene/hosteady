import { useState } from 'react'
import type { CellRendererProps } from './types'
import { DocumentDetailDrawer } from '@/features/documents/components/DocumentDetailDrawer'
import { resolveDocumentIcon } from '@/features/documents/core/document-icons'
import { resolveDocumentReferenceFromValue } from '@/features/documents/core/document-selection'

export function DocumentCell({ value }: CellRendererProps) {
  const [open, setOpen] = useState(false)
  const reference = resolveDocumentReferenceFromValue(value)

  if (!reference) {
    return <span className="text-muted-foreground">—</span>
  }

  return (
    <>
      <button
        type="button"
        className="inline-flex items-center gap-2 text-sm text-foreground hover:underline"
        aria-label={`Open document ${reference.title}`}
        onClick={() => setOpen(true)}
      >
        <span className="inline-flex h-6 w-6 items-center justify-center rounded bg-muted text-[10px] font-semibold">
          {resolveDocumentIcon(reference.document_type)}
        </span>
        <span>{reference.title}</span>
      </button>
      <DocumentDetailDrawer
        document={{
          ...reference,
          filename: reference.title,
        }}
        open={open}
        onClose={() => setOpen(false)}
      />
    </>
  )
}
