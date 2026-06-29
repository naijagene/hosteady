import type { DocumentItem } from '@/api/types/documents'
import { DocumentRow } from './DocumentRow'

interface DocumentListProps {
  documents: DocumentItem[]
  selectionEnabled?: boolean
  selectedIds?: string[]
  onOpen?: (document: DocumentItem) => void
  onToggleSelect?: (document: DocumentItem) => void
}

export function DocumentList({
  documents,
  selectionEnabled = false,
  selectedIds = [],
  onOpen,
  onToggleSelect,
}: DocumentListProps) {
  return (
    <div className="overflow-x-auto rounded-md border border-border" data-testid="document-list">
      <table className="min-w-full text-left">
        <caption className="sr-only">Documents</caption>
        <thead className="border-b border-border bg-muted/30">
          <tr>
            {selectionEnabled ? <th className="px-3 py-2">Select</th> : null}
            <th className="px-3 py-2 text-sm font-medium">Title</th>
            <th className="px-3 py-2 text-sm font-medium">Type</th>
            <th className="px-3 py-2 text-sm font-medium">Size</th>
            <th className="px-3 py-2 text-sm font-medium">Status</th>
            <th className="px-3 py-2 text-sm font-medium">Updated</th>
            <th className="px-3 py-2 text-sm font-medium">Versions</th>
            <th className="px-3 py-2 text-sm font-medium">Attachments</th>
          </tr>
        </thead>
        <tbody role="list">
          {documents.map((document) => (
            <DocumentRow
              key={document.public_id}
              document={document}
              selectionEnabled={selectionEnabled}
              selected={selectedIds.includes(document.public_id)}
              onOpen={() => onOpen?.(document)}
              onToggleSelect={() => onToggleSelect?.(document)}
            />
          ))}
        </tbody>
      </table>
    </div>
  )
}
