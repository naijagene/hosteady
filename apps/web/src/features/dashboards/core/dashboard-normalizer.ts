import type { DashboardRenderPayload } from '@/api/types/dashboards'
import type { NormalizedDashboardModel } from '../types'

export function normalizeDashboardRenderModel(
  payload: DashboardRenderPayload,
): NormalizedDashboardModel {
  const widgetDataMap = new Map(
    (payload.widget_data ?? []).map((item) => [item.widget_key, item]),
  )

  return {
    definition: payload.dashboard,
    widgets: payload.widgets ?? [],
    layout: payload.layout ?? payload.dashboard.layout ?? null,
    filters: payload.filters ?? payload.dashboard.filters ?? [],
    actions: payload.actions ?? payload.dashboard.actions ?? [],
    widgetDataMap,
  }
}

export function getDashboardTitle(payload: DashboardRenderPayload): string {
  return payload.dashboard.name || 'Dashboard'
}

export function getDashboardDescription(payload: DashboardRenderPayload): string | null {
  return payload.dashboard.description ?? null
}
