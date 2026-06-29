import { useMemo } from 'react'
import type { DashboardBindingContext, DashboardRenderPayload } from '@/api/types/dashboards'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import { filterActionsByPermission, filterWidgetsByPermission } from '../core/dashboard-permissions'
import {
  attachWidgetData,
  normalizeWidgetType,
  resolveDashboardWidgets,
} from '../core/dashboard-widgets'
import {
  getDashboardDescription,
  getDashboardTitle,
  normalizeDashboardRenderModel,
} from '../core/dashboard-normalizer'
import { resolveDashboardToolbarActions, getDefaultDashboardActions } from '../core/dashboard-actions'
import type { ResolvedDashboardWidget } from '../types'
import { useDashboardPersonalization } from './useDashboardPersonalization'

export function useDashboardRender(options: {
  payload: DashboardRenderPayload
  binding?: DashboardBindingContext
}) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(
    () => runtime?.permissions ?? [],
    [runtime?.permissions],
  )

  const model = useMemo(
    () => normalizeDashboardRenderModel(options.payload),
    [options.payload],
  )

  const personalization = useDashboardPersonalization({
    enabled: options.binding?.personalization_enabled !== false,
  })

  const widgets = useMemo(() => {
    const filtered = filterWidgetsByPermission(model.widgets, permissions)
    const resolved = resolveDashboardWidgets(filtered, {
      hiddenKeys: personalization.hiddenWidgetKeys,
      order: personalization.widgetOrder,
    })

    return attachWidgetData(resolved, model.widgetDataMap).map((widget) => ({
      ...widget,
      widgetType: normalizeWidgetType(widget.widget_type),
      collapsed: personalization.collapsedWidgetKeys.has(widget.widget_key),
      hidden: personalization.hiddenWidgetKeys.has(widget.widget_key),
    })) as Array<ResolvedDashboardWidget & { widgetType: string }>
  }, [model, permissions, personalization])

  const actions = useMemo(() => {
    const defaults = getDefaultDashboardActions(options.binding?.refresh_enabled !== false)
    const resolved = resolveDashboardToolbarActions([...defaults, ...model.actions])
    const seen = new Set<string>()

    return filterActionsByPermission(
      resolved.filter((action) => {
        if (seen.has(action.action_key)) {
          return false
        }

        seen.add(action.action_key)
        return true
      }),
      permissions,
    )
  }, [model.actions, options.binding, permissions])

  return {
    title: getDashboardTitle(options.payload),
    description: getDashboardDescription(options.payload),
    layout: model.layout,
    widgets,
    filters: model.filters,
    actions,
    permissions,
    personalization,
  }
}
