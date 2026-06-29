import { useQuery } from '@tanstack/react-query'
import { fetchReportRender } from '@/api/endpoints/reports'
import type { UiComponent } from '@/api/types/ui'
import { useComponentBinding } from '../hooks/useComponentBinding'

interface ReportBindingRendererProps {
  component: UiComponent
}

export function ReportBindingRenderer({ component }: ReportBindingRendererProps) {
  const binding = useComponentBinding(component)
  const query = useQuery({
    queryKey: ['report-render', binding?.moduleKey, binding?.resourceKey],
    queryFn: () => fetchReportRender(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey),
  })

  if (!binding) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="report-binding-missing">
        Report binding unavailable
      </div>
    )
  }

  if (query.isLoading) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="report-binding-loading">
        Loading report…
      </div>
    )
  }

  const sections = query.data?.sections ?? []

  return (
    <section
      className="rounded-lg border border-border bg-card p-4"
      data-testid="report-binding-renderer"
    >
      <h4 className="text-sm font-medium text-foreground">
        {query.data?.report.name ?? component.name}
      </h4>
      <div className="mt-4 space-y-2">
        {sections.length === 0 ? (
          <p className="text-xs text-muted-foreground">Report sections placeholder</p>
        ) : (
          sections.map((_section, index) => (
            <div
              key={index}
              className="rounded-md border border-dashed border-border bg-muted/20 p-3 text-xs text-muted-foreground"
            >
              Section {index + 1}
            </div>
          ))
        )}
      </div>
    </section>
  )
}
