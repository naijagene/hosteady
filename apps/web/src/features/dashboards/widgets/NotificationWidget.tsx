import { DashboardNotificationWidget } from '../components/DashboardNotificationWidget'
import type { DashboardWidgetComponentProps } from './types'

export function NotificationWidget({ widget }: DashboardWidgetComponentProps) {
  const items = (widget.data?.rows ?? widget.data?.metadata?.items ?? []) as Array<{
    title?: string
    message?: string
  }>

  return <DashboardNotificationWidget title={widget.label} items={items} />
}
