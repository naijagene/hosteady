import { Link } from '@tanstack/react-router'
import { buildMetricDisplay } from '../core/dashboard-metrics'
import type { DashboardWidgetComponentProps } from './types'

function resolveReportRoute(widget: DashboardWidgetComponentProps['widget']): {
  moduleKey?: string
  reportKey?: string
} {
  const metadata = widget.metadata ?? {}
  const dataMetadata = widget.data?.metadata ?? {}

  return {
    moduleKey: String(
      metadata.module_key ??
        metadata.moduleKey ??
        dataMetadata.module_key ??
        dataMetadata.moduleKey ??
        '',
    ) || undefined,
    reportKey: String(
      metadata.report_key ??
        metadata.reportKey ??
        dataMetadata.report_key ??
        dataMetadata.reportKey ??
        '',
    ) || undefined,
  }
}

export function ReportWidget({ widget }: DashboardWidgetComponentProps) {
  const { moduleKey, reportKey } = resolveReportRoute(widget)
  const display = buildMetricDisplay(widget.metric, widget.data, widget.label)

  return (
    <div className="space-y-2" data-testid="report-widget">
      <h4 className="text-sm font-medium text-foreground">{widget.label}</h4>
      {display.empty ? (
        <p className="text-xs text-muted-foreground">
          Report summary will appear here when metadata is available.
        </p>
      ) : (
        <div className="rounded-md border border-border bg-muted/10 p-2">
          <p className="text-xs text-muted-foreground">{display.title}</p>
          <p className="text-lg font-semibold text-foreground">{display.value}</p>
        </div>
      )}
      {moduleKey && reportKey ? (
        <Link
          to="/reports/$moduleKey/$reportKey"
          params={{ moduleKey, reportKey }}
          className="inline-flex text-xs text-primary underline-offset-2 hover:underline"
          aria-label={`Open report ${widget.label}`}
        >
          Open full report
        </Link>
      ) : (
        <p className="text-xs text-muted-foreground">Report route metadata unavailable.</p>
      )}
    </div>
  )
}
