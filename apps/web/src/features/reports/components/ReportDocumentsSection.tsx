import type { DocumentReference } from '@/api/types/documents'
import { normalizeDocumentReference } from '@/api/types/documents'
import { asArray } from '@/api/types/metadata-common'
import { resolveDocumentIcon } from '@/features/documents/core/document-icons'

interface ReportDocumentsSectionProps {
  title?: string
  documents: unknown[]
}

export function ReportDocumentsSection({ title = 'Documents', documents }: ReportDocumentsSectionProps) {
  const items = asArray(documents).map(normalizeDocumentReference)

  return (
    <section className="space-y-2" data-testid="report-documents-section" aria-label={title}>
      <h3 className="text-sm font-medium text-foreground">{title}</h3>
      {items.length === 0 ? (
        <p className="text-xs text-muted-foreground">No document references included in this report.</p>
      ) : (
        <ul className="space-y-2">
          {items.map((document: DocumentReference) => (
            <li key={document.public_id} className="flex items-center gap-2 rounded-md border border-border px-3 py-2 text-xs">
              <span className="inline-flex h-6 w-6 items-center justify-center rounded bg-muted text-[10px] font-semibold">
                {resolveDocumentIcon(document.document_type)}
              </span>
              <span className="text-foreground">{document.title}</span>
              <span className="text-muted-foreground">{document.status ?? 'reference'}</span>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
