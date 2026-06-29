import { DashboardFavorites } from '../components/DashboardFavorites'
import type { DashboardWidgetComponentProps } from './types'

export function FavoritesWidget({ widget }: DashboardWidgetComponentProps) {
  const items = (widget.data?.rows ?? widget.data?.metadata?.items ?? []) as Array<{
    label?: string
    route?: string
  }>

  return <DashboardFavorites title={widget.label} items={items} />
}
