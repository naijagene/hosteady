import type { DocumentItem } from '@/api/types/documents'
import { DocumentCard } from './DocumentCard'

interface DocumentGridProps {
  documents: DocumentItem[]
  selectionEnabled?: boolean
  selectedIds?: string[]
  onOpen?: (document: DocumentItem) => void
  onToggleSelect?: (document: DocumentItem) => void
}

export function DocumentGrid({
  documents,
  selectionEnabled = false,
  selectedIds = [],
  onOpen,
  onToggleSelect,
}: DocumentGridProps) {
  return (
    <div
      className="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
      data-testid="document-grid"
      role="list"
      aria-label="Documents grid"
    >
      {documents.map((document) => (
        <DocumentCard
          key={document.public_id}
          document={document}
          selectionEnabled={selectionEnabled}
          selected={selectedIds.includes(document.public_id)}
          onOpen={() => onOpen?.(document)}
          onToggleSelect={() => onToggleSelect?.(document)}
        />
      ))}
    </div>
  )
}
