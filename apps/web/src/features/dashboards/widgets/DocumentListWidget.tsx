import { Link } from '@tanstack/react-router'
import { DocumentManager } from '@/features/documents/components/DocumentManager'
import type { DashboardWidgetComponentProps } from './types'

export function DocumentListWidget({ widget }: DashboardWidgetComponentProps) {
  const compact = widget.metadata?.compact === true

  if (compact) {
    return (
      <div data-testid="document-list-widget">
        <DocumentManager
          title={widget.label}
          binding={{
            mode: 'compact',
            query_enabled: true,
            search_enabled: false,
            upload_enabled: false,
            detail_enabled: true,
            per_page: 5,
          }}
        />
      </div>
    )
  }

  return (
    <div className="space-y-2" data-testid="document-list-widget">
      <h4 className="text-sm font-medium text-foreground">{widget.label}</h4>
      <p className="text-xs text-muted-foreground">
        Recent documents and document count metrics appear here when widget data is available.
      </p>
      <p className="text-xs text-muted-foreground">
        Documents: {String(widget.data?.value ?? widget.data?.metadata?.documents_count ?? '—')}
      </p>
      <Link
        to="/documents"
        className="inline-flex text-xs text-primary underline-offset-2 hover:underline"
        aria-label={`Open documents for ${widget.label}`}
      >
        Open document manager
      </Link>
    </div>
  )
}
