import type { DocumentItem } from '@/api/types/documents'
import {
  formatDocumentDate,
  formatDocumentSize,
  getDocumentDisplayTitle,
  getDocumentSubtitle,
} from '../core/document-normalizer'
import { resolveDocumentIcon } from '../core/document-icons'

interface DocumentCardProps {
  document: DocumentItem
  selected?: boolean
  selectionEnabled?: boolean
  onOpen?: () => void
  onToggleSelect?: () => void
}

export function DocumentCard({
  document,
  selected = false,
  selectionEnabled = false,
  onOpen,
  onToggleSelect,
}: DocumentCardProps) {
  return (
    <article
      className="rounded-lg border border-border bg-card p-4"
      data-testid={`document-card-${document.public_id}`}
      aria-label={`Document ${getDocumentDisplayTitle(document)}`}
    >
      <div className="flex items-start gap-3">
        <div className="flex h-10 w-10 items-center justify-center rounded-md bg-muted text-xs font-semibold text-foreground">
          {resolveDocumentIcon(document.mime_type, document.document_type)}
        </div>
        <div className="min-w-0 flex-1">
          <button
            type="button"
            className="truncate text-left text-sm font-medium text-foreground hover:underline"
            aria-label={`Open ${getDocumentDisplayTitle(document)}`}
            onClick={onOpen}
          >
            {getDocumentDisplayTitle(document)}
          </button>
          <p className="text-xs text-muted-foreground">{getDocumentSubtitle(document)}</p>
          <p className="mt-2 text-xs text-muted-foreground">
            {formatDocumentSize(document.size_bytes)} · {formatDocumentDate(document.updated_at)}
          </p>
          {document.tags?.length ? (
            <div className="mt-2 flex flex-wrap gap-1">
              {document.tags.map((tag) => (
                <span key={tag.tag_key} className="rounded-full bg-muted px-2 py-0.5 text-[10px] text-foreground">
                  {tag.label}
                </span>
              ))}
            </div>
          ) : null}
        </div>
        {selectionEnabled ? (
          <input
            type="checkbox"
            checked={selected}
            aria-label={`Select ${getDocumentDisplayTitle(document)}`}
            onChange={onToggleSelect}
          />
        ) : null}
      </div>
    </article>
  )
}
