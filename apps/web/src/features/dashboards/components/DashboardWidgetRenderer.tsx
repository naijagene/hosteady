import { renderDashboardWidget } from '../widgets'
import type { ResolvedDashboardWidget } from '../types'
import { DashboardWidgetShell } from './DashboardWidgetShell'

interface DashboardWidgetRendererProps {
  widget: ResolvedDashboardWidget & { widgetType: string; collapsed?: boolean }
  widgetType: string
}

export function DashboardWidgetRenderer({ widget, widgetType }: DashboardWidgetRendererProps) {
  return (
    <DashboardWidgetShell
      title={widget.label}
      description={widget.description}
      collapsed={widget.collapsed}
    >
      {renderDashboardWidget({
        widget,
        widgetType,
        collapsed: widget.collapsed,
      })}
    </DashboardWidgetShell>
  )
}
