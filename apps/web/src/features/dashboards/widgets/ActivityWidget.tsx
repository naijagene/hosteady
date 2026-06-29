import { DashboardActivityFeed } from '../components/DashboardActivityFeed'
import type { DashboardWidgetComponentProps } from './types'

export function ActivityWidget({ widget }: DashboardWidgetComponentProps) {
  const items = (widget.data?.rows ?? widget.data?.metadata?.items ?? []) as Array<{
    title?: string
    description?: string
    timestamp?: string
  }>

  return <DashboardActivityFeed title={widget.label} items={items} />
}
