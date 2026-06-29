import type { DashboardWidgetComponentProps } from './types'

export function ReportWidget({ widget }: DashboardWidgetComponentProps) {
  return (
    <div className="space-y-2" data-testid="report-widget">
      <h4 className="text-sm font-medium text-foreground">{widget.label}</h4>
      <p className="text-xs text-muted-foreground">
        Report widget placeholder. Open report action is not implemented yet.
      </p>
    </div>
  )
}
