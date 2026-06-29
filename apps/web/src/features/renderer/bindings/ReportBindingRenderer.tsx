import { useQuery } from '@tanstack/react-query'
import { fetchReportRender } from '@/api/endpoints/reports'
import { normalizeReportBindingContext } from '@/api/types/reports'
import type { UiComponent } from '@/api/types/ui'
import {
  DynamicReportViewer,
  ReportErrorState,
  ReportLoadingState,
} from '@/features/reports'
import { toReportQueryError } from '@/features/reports/core/report-errors'
import { useComponentBinding } from '../hooks/useComponentBinding'
import { useOptionalRendererContext } from '../hooks/useRendererContext'

interface ReportBindingRendererProps {
  component: UiComponent
}

function bindingRenderEnabled(config: Record<string, unknown>): boolean {
  return config.auto_render === true || config.autoRender === true || config.render_enabled === true
}

export function ReportBindingRenderer({ component }: ReportBindingRendererProps) {
  const binding = useComponentBinding(component)
  const rendererContext = useOptionalRendererContext()
  const shouldRender = bindingRenderEnabled(binding?.config ?? {})
  const query = useQuery({
    queryKey: ['report-render', binding?.moduleKey, binding?.resourceKey],
    queryFn: () => fetchReportRender(binding!.moduleKey, binding!.resourceKey),
    enabled: Boolean(binding?.moduleKey && binding?.resourceKey && shouldRender),
  })

  if (!binding) {
    return (
      <div className="text-sm text-muted-foreground" data-testid="report-binding-missing">
        Report binding unavailable
      </div>
    )
  }

  if (!shouldRender) {
    return (
      <div
        className="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground"
        data-testid="report-binding-renderer"
      >
        Report binding configured without auto render.
      </div>
    )
  }

  if (query.isLoading) {
    return (
      <div data-testid="report-binding-loading">
        <ReportLoadingState />
      </div>
    )
  }

  if (query.isError || !query.data) {
    return (
      <div data-testid="report-binding-error">
        <ReportErrorState
          message={
            query.error
              ? toReportQueryError(query.error).message
              : 'Unable to load report metadata.'
          }
        />
      </div>
    )
  }

  const reportBinding = normalizeReportBindingContext(
    {
      ...binding.config,
      ...component.binding_config,
      page: rendererContext?.pageKey,
      binding: component.component_key,
      auto_render: shouldRender,
    },
    binding.moduleKey,
    binding.resourceKey,
  )

  return (
    <div data-testid="report-binding-renderer">
      <DynamicReportViewer
        payload={query.data}
        binding={reportBinding}
        onRefresh={() => query.refetch()}
      />
    </div>
  )
}
