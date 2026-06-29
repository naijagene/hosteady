import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface DashboardWidget {
  widget_key: string
  label: string
  widget_type?: string
  metadata?: MetadataRecord
}

export interface DashboardDefinition {
  public_id?: string
  module_key: string
  dashboard_key: string
  name: string
  description?: string | null
  widgets?: DashboardWidget[]
  metadata?: MetadataRecord
}

export interface DashboardRenderPayload {
  dashboard: DashboardDefinition
  widgets: DashboardWidget[]
  layout?: MetadataRecord
  metadata?: MetadataRecord
}

export function normalizeDashboardDefinition(raw: unknown): DashboardDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    dashboard_key: asString(data.dashboard_key ?? data.dashboardKey),
    name: asString(data.name, 'Dashboard'),
    description:
      typeof data.description === 'string' ? data.description : null,
    widgets: asArray(data.widgets).map((widget) => {
      const item = asRecord(widget)
      return {
        widget_key: asString(item.widget_key ?? item.widgetKey),
        label: asString(item.label, 'Widget'),
        widget_type: asString(item.widget_type ?? item.widgetType),
        metadata: asRecord(item.metadata),
      }
    }),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeDashboardRenderPayload(
  raw: unknown,
): DashboardRenderPayload {
  const data = asRecord(raw)
  const dashboard = normalizeDashboardDefinition(
    data.dashboard ?? data.definition ?? data,
  )

  return {
    dashboard,
    widgets:
      asArray(data.widgets).length > 0
        ? asArray(data.widgets).map((widget) => {
            const item = asRecord(widget)
            return {
              widget_key: asString(item.widget_key ?? item.widgetKey),
              label: asString(item.label, 'Widget'),
              widget_type: asString(item.widget_type ?? item.widgetType),
              metadata: asRecord(item.metadata),
            }
          })
        : dashboard.widgets ?? [],
    layout: asRecord(data.layout),
    metadata: asRecord(data.metadata),
  }
}
