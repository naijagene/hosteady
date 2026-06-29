import { useQuery } from '@tanstack/react-query'
import { fetchDocuments } from '@/api/endpoints/documents'
import type { UiComponent } from '@/api/types/ui'

interface DocumentBindingRendererProps {
  component: UiComponent
}

export function DocumentBindingRenderer({ component }: DocumentBindingRendererProps) {
  const query = useQuery({
    queryKey: ['documents-list'],
    queryFn: fetchDocuments,
  })

  if (query.isLoading) {
    return (
      <div
        className="text-sm text-muted-foreground"
        data-testid="document-binding-loading"
      >
        Loading documents…
      </div>
    )
  }

  const documents = query.data ?? []

  return (
    <section
      className="rounded-lg border border-border bg-card p-4"
      data-testid="document-binding-renderer"
    >
      <h4 className="text-sm font-medium text-foreground">{component.name}</h4>
      <ul className="mt-3 space-y-2">
        {documents.length === 0 ? (
          <li className="text-xs text-muted-foreground">No documents available</li>
        ) : (
          documents.slice(0, 5).map((document) => (
            <li
              key={document.public_id}
              className="rounded-md border border-border px-3 py-2 text-xs text-foreground"
            >
              {document.title}
            </li>
          ))
        )}
      </ul>
    </section>
  )
}
