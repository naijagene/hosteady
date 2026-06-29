import { useMemo } from 'react'
import type { ReportBindingContext, ReportRenderPayload } from '@/api/types/reports'
import { useHydratedRuntime } from '@/features/runtime/use-hydrated-runtime'
import {
  filterActionsByPermission,
  canExportReport,
  canRunReport,
} from '../core/report-permissions'
import {
  getDefaultReportActions,
  resolveReportToolbarActions,
} from '../core/report-actions'
import {
  getReportDescription,
  getReportTitle,
  normalizeReportRenderModel,
} from '../core/report-normalizer'

function resolveReportPermissions(
  permissions: ReportRenderPayload['permissions'],
): string[] | Record<string, boolean> | undefined {
  if (Array.isArray(permissions)) {
    return permissions
  }

  if (permissions && typeof permissions === 'object') {
    return Object.entries(permissions).reduce<Record<string, boolean>>((accumulator, [key, value]) => {
      accumulator[key] = value !== false
      return accumulator
    }, {})
  }

  return undefined
}

export function useReportRender(options: {
  payload: ReportRenderPayload
  binding?: ReportBindingContext
}) {
  const runtime = useHydratedRuntime()
  const permissions = useMemo(
    () => runtime?.permissions ?? [],
    [runtime?.permissions],
  )

  const model = useMemo(
    () => normalizeReportRenderModel(options.payload),
    [options.payload],
  )

  const reportPermissions = useMemo(
    () => resolveReportPermissions(options.payload.permissions),
    [options.payload.permissions],
  )

  const actions = useMemo(() => {
    const defaults = getDefaultReportActions({
      runEnabled:
        options.binding?.run_enabled !== false && canRunReport(reportPermissions),
      exportEnabled:
        options.binding?.export_enabled !== false && canExportReport(reportPermissions),
    })
    const resolved = resolveReportToolbarActions([...defaults, ...model.actions])
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
  }, [model.actions, options.binding, reportPermissions, permissions])

  return {
    title: getReportTitle(options.payload),
    description: getReportDescription(options.payload),
    parameters: model.parameters,
    sections: model.sections,
    metrics: model.metrics,
    actions,
    permissions,
  }
}
