import { useMemo } from 'react'
import { asArray, asRecord, asString } from '@/api/types/metadata-common'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import type { DashboardPersonalizationState } from '../types'

export function useDashboardPersonalization(options?: { enabled?: boolean }) {
  const runtime = useHydratedRuntime()
  const enabled = options?.enabled !== false
  const overrides = asRecord(runtime?.personalizationRuntime?.dashboard_overrides)

  const hiddenWidgetKeys = new Set(
    asArray<string>(overrides.hidden_widgets ?? overrides.hiddenWidgets).map((item) => asString(item)),
  )
  const collapsedWidgetKeys = new Set(
    asArray<string>(overrides.collapsed_widgets ?? overrides.collapsedWidgets).map((item) => asString(item)),
  )
  const widgetOrder = asArray<string>(overrides.widget_order ?? overrides.widgetOrder).map((item) => asString(item))
  const density = asString(overrides.layout_density ?? overrides.layoutDensity, 'comfortable')

  if (!enabled || !runtime?.personalizationRuntime) {
    return {
      hiddenWidgetKeys: new Set<string>(),
      collapsedWidgetKeys: new Set<string>(),
      widgetOrder: [],
      layoutDensity: 'comfortable',
    } satisfies DashboardPersonalizationState
  }

  return {
    hiddenWidgetKeys,
    collapsedWidgetKeys,
    widgetOrder,
    layoutDensity: density === 'compact' ? 'compact' : 'comfortable',
  } satisfies DashboardPersonalizationState
}

export function useHomePersonalization() {
  const runtime = useHydratedRuntime()

  return useMemo(() => {
    const personalization = runtime?.personalizationRuntime

    return {
      favorites: personalization?.favorites ?? [],
      recentItems: personalization?.recent_items ?? [],
      shortcuts: personalization?.shortcuts ?? [],
      quickActions: personalization?.quick_actions ?? [],
      dashboardOverrides: personalization?.dashboard_overrides ?? {},
      source: personalization?.source ?? 'Unavailable',
    }
  }, [runtime?.personalizationRuntime])
}
