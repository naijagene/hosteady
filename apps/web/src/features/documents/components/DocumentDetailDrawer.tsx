import type { DocumentItem } from '@/api/types/documents'
import { getDocumentActionPlaceholder } from '../core/document-actions'
import { useDocumentDetail } from '../hooks/useDocumentDetail'
import { DocumentAttachmentList } from './DocumentAttachmentList'
import { DocumentMetadataPanel } from './DocumentMetadataPanel'
import { DocumentVersionList } from './DocumentVersionList'

interface DocumentDetailDrawerProps {
  document: DocumentItem | null
  open: boolean
  onClose: () => void
  canDownload?: boolean
}

export function DocumentDetailDrawer({
  document,
  open,
  onClose,
  canDownload = true,
}: DocumentDetailDrawerProps) {
  const detail = useDocumentDetail(document?.public_id ?? null, open)

  if (!open || !document) {
    return null
  }

  const resolvedDocument = detail.document ?? document

  return (
    <aside
      className="fixed inset-y-0 right-0 z-40 w-full max-w-md border-l border-border bg-card shadow-lg"
      data-testid="document-detail-drawer"
      role="dialog"
      aria-modal="true"
      aria-label={`Document details for ${resolvedDocument.title}`}
    >
      <div className="flex items-center justify-between border-b border-border px-4 py-3">
        <h3 className="text-sm font-semibold text-foreground">Document details</h3>
        <button type="button" className="text-xs text-muted-foreground hover:text-foreground" aria-label="Close document details" onClick={onClose}>
          Close
        </button>
      </div>
      <div className="space-y-4 overflow-y-auto p-4">
        <DocumentMetadataPanel document={resolvedDocument} />
        <DocumentVersionList versions={detail.versions} />
        <DocumentAttachmentList attachments={detail.attachments} />
        <section className="space-y-2">
          <h4 className="text-sm font-medium text-foreground">Actions</h4>
          {canDownload ? (
            detail.downloadUrl ? (
              <a href={detail.downloadUrl} className="text-xs text-primary underline-offset-2 hover:underline">
                Download document
              </a>
            ) : (
              <p className="text-xs text-muted-foreground">Download link unavailable. Preview rendering is not enabled.</p>
            )
          ) : null}
          <p className="text-xs text-muted-foreground">{getDocumentActionPlaceholder('replace_version')}</p>
          <p className="text-xs text-muted-foreground">{getDocumentActionPlaceholder('attach_record')}</p>
          <p className="text-xs text-muted-foreground">Activity timeline placeholder.</p>
        </section>
        {detail.error ? (
          <p className="text-xs text-destructive" role="alert">
            {detail.error.message}
          </p>
        ) : null}
      </div>
    </aside>
  )
}
