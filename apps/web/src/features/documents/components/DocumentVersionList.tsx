import type { DocumentVersion } from '@/api/types/documents'
import { formatDocumentDate } from '../core/document-normalizer'

interface DocumentVersionListProps {
  versions: DocumentVersion[]
}

export function DocumentVersionList({ versions }: DocumentVersionListProps) {
  return (
    <section className="space-y-2" data-testid="document-version-list" aria-label="Document versions">
      <h4 className="text-sm font-medium text-foreground">Versions</h4>
      {versions.length === 0 ? (
        <p className="text-xs text-muted-foreground">No versions available.</p>
      ) : (
        <ul className="space-y-2">
          {versions.map((version) => (
            <li key={version.public_id} className="rounded-md border border-border px-3 py-2 text-xs">
              <div className="font-medium text-foreground">
                v{version.version_number} {version.label ? `· ${version.label}` : ''}
              </div>
              <div className="text-muted-foreground">{formatDocumentDate(version.created_at)}</div>
            </li>
          ))}
        </ul>
      )}
    </section>
  )
}
