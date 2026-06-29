import type { DocumentItem } from '@/api/types/documents'
import {
  formatDocumentDate,
  formatDocumentSize,
  getDocumentDisplayTitle,
  getDocumentSubtitle,
} from '../core/document-normalizer'

interface DocumentMetadataPanelProps {
  document: DocumentItem
}

export function DocumentMetadataPanel({ document }: DocumentMetadataPanelProps) {
  return (
    <section className="space-y-3" data-testid="document-metadata-panel" aria-label="Document metadata">
      <div>
        <h3 className="text-sm font-medium text-foreground">{getDocumentDisplayTitle(document)}</h3>
        <p className="text-xs text-muted-foreground">{document.description ?? 'No description provided.'}</p>
      </div>
      <dl className="grid gap-2 text-xs sm:grid-cols-2">
        <div><dt className="text-muted-foreground">Type</dt><dd>{getDocumentSubtitle(document)}</dd></div>
        <div><dt className="text-muted-foreground">Size</dt><dd>{formatDocumentSize(document.size_bytes)}</dd></div>
        <div><dt className="text-muted-foreground">Status</dt><dd>{document.status ?? '—'}</dd></div>
        <div><dt className="text-muted-foreground">Owner</dt><dd>{document.owner ?? document.uploader ?? '—'}</dd></div>
        <div><dt className="text-muted-foreground">Created</dt><dd>{formatDocumentDate(document.created_at)}</dd></div>
        <div><dt className="text-muted-foreground">Updated</dt><dd>{formatDocumentDate(document.updated_at)}</dd></div>
      </dl>
      {document.tags?.length ? (
        <div className="flex flex-wrap gap-1">
          {document.tags.map((tag) => (
            <span key={tag.tag_key} className="rounded-full bg-muted px-2 py-0.5 text-[10px]">
              {tag.label}
            </span>
          ))}
        </div>
      ) : null}
    </section>
  )
}
