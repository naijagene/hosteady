import { DashboardRecentItems } from '../components/DashboardRecentItems'
import type { DashboardWidgetComponentProps } from './types'

export function RecentItemsWidget({ widget }: DashboardWidgetComponentProps) {
  const items = (widget.data?.rows ?? widget.data?.metadata?.items ?? []) as Array<{
    label?: string
    route?: string
  }>

  return <DashboardRecentItems title={widget.label} items={items} />
}
