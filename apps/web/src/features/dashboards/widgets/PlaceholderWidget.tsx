import type { DashboardWidgetComponentProps } from './types'

export function PlaceholderWidget({ widget, widgetType }: DashboardWidgetComponentProps) {
  return (
    <div className="space-y-2" data-testid="placeholder-widget">
      <h4 className="text-sm font-medium text-foreground">{widget.label}</h4>
      <p className="text-xs text-muted-foreground">
        Unsupported widget type: {widgetType}
      </p>
    </div>
  )
}
