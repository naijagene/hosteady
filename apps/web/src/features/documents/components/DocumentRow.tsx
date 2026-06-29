import type { DocumentItem } from '@/api/types/documents'
import {
  formatDocumentDate,
  formatDocumentSize,
  getDocumentDisplayTitle,
  getDocumentSubtitle,
} from '../core/document-normalizer'
import { resolveDocumentIcon } from '../core/document-icons'

interface DocumentRowProps {
  document: DocumentItem
  selected?: boolean
  selectionEnabled?: boolean
  onOpen?: () => void
  onToggleSelect?: () => void
}

export function DocumentRow({
  document,
  selected = false,
  selectionEnabled = false,
  onOpen,
  onToggleSelect,
}: DocumentRowProps) {
  return (
    <tr data-testid={`document-row-${document.public_id}`}>
      {selectionEnabled ? (
        <td className="px-3 py-2">
          <input
            type="checkbox"
            checked={selected}
            aria-label={`Select ${getDocumentDisplayTitle(document)}`}
            onChange={onToggleSelect}
          />
        </td>
      ) : null}
      <td className="px-3 py-2">
        <div className="flex items-center gap-2">
          <span className="inline-flex h-8 w-8 items-center justify-center rounded bg-muted text-[10px] font-semibold">
            {resolveDocumentIcon(document.mime_type, document.document_type)}
          </span>
          <button
            type="button"
            className="text-left text-sm text-foreground hover:underline"
            aria-label={`Open ${getDocumentDisplayTitle(document)}`}
            onClick={onOpen}
          >
            {getDocumentDisplayTitle(document)}
          </button>
        </div>
      </td>
      <td className="px-3 py-2 text-sm text-muted-foreground">{getDocumentSubtitle(document)}</td>
      <td className="px-3 py-2 text-sm text-muted-foreground">{formatDocumentSize(document.size_bytes)}</td>
      <td className="px-3 py-2 text-sm text-muted-foreground">{document.status ?? '—'}</td>
      <td className="px-3 py-2 text-sm text-muted-foreground">{formatDocumentDate(document.updated_at)}</td>
      <td className="px-3 py-2 text-sm text-muted-foreground">{document.version_count ?? document.current_version_number ?? '—'}</td>
      <td className="px-3 py-2 text-sm text-muted-foreground">{document.attachment_count ?? '—'}</td>
    </tr>
  )
}
