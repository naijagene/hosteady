import { DashboardQuickActions } from '../components/DashboardQuickActions'
import type { DashboardWidgetComponentProps } from './types'

export function QuickActionsWidget({ widget }: DashboardWidgetComponentProps) {
  const actions = (widget.data?.metadata?.actions ?? widget.metadata?.actions ?? []) as Array<{
    label?: string
    action_key?: string
  }>

  return <DashboardQuickActions title={widget.label} actions={actions} />
}
