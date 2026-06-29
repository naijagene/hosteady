import type { DocumentAttachment } from '@/api/types/documents'
import { formatDocumentDate } from '../core/document-normalizer'

interface DocumentAttachmentListProps {
  attachments: DocumentAttachment[]
}

export function DocumentAttachmentList({ attachments }: DocumentAttachmentListProps) {
  return (
    <section className="space-y-2" data-testid="document-attachment-list" aria-label="Document attachments">
      <h4 className="text-sm font-medium text-foreground">Attachments</h4>
      {attachments.length === 0 ? (
        <p className="text-xs text-muted-foreground">No attachments linked to this document.</p>
      ) : (
        <ul className="space-y-2">
          {attachments.map((attachment) => (
            <li key={attachment.public_id} className="rounded-md border border-border px-3 py-2 text-xs">
              <div className="font-medium text-foreground">{attachment.subject_type}</div>
              <div className="text-muted-foreground">{attachment.subject_public_id}</div>
              <div className="text-muted-foreground">{formatDocumentDate(attachment.created_at)}</div>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
