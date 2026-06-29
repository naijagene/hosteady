import {
  asArray,
  asNumber,
  asRecord,
  asString,
  type MetadataRecord,
} from './metadata-common'

export interface UiAction {
  action_key: string
  label: string
  action_type?: string
  permission?: string | null
  metadata?: MetadataRecord
}

export interface UiCondition {
  condition_key: string
  field?: string
  operator?: string
  value?: unknown
  permission?: string | null
  metadata?: MetadataRecord
}

export interface UiComponentBinding {
  binding_type: string
  public_id?: string | null
  module_key?: string | null
  resource_key?: string | null
  config?: MetadataRecord
}

export interface UiComponent {
  public_id: string
  component_key: string
  name: string
  description?: string | null
  component_type: string
  status?: string
  binding_type?: string | null
  binding_config?: MetadataRecord
  binding?: UiComponentBinding
  actions?: UiAction[]
  conditions?: UiCondition[]
  metadata?: MetadataRecord
  module_key?: string | null
  region_key?: string
  sort_order?: number
  permission?: string | null
}

export interface UiRegion {
  region_key: string
  region_type: string
  label: string
  sort_order: number
  components: (string | UiComponent)[]
  breakpoints?: MetadataRecord[]
  metadata?: MetadataRecord
}

export interface UiLayout {
  public_id?: string
  layout_key: string
  name: string
  description?: string | null
  layout_type: string
  status?: string
  regions?: UiRegion[]
  breakpoints?: MetadataRecord[]
  metadata?: MetadataRecord
  module_key?: string | null
}

export interface UiPage {
  public_id?: string
  page_key: string
  name: string
  description?: string | null
  page_type?: string
  status?: string
  module_key?: string
  layout_key?: string
  permission?: string | null
  metadata?: MetadataRecord
}

export interface UiRenderPayload {
  page: UiPage
  layout: UiLayout
  regions: UiRegion[]
  components: UiComponent[]
  actions: UiAction[]
  conditions: UiCondition[]
  breakpoints: MetadataRecord[]
  theme: MetadataRecord
  personalization: MetadataRecord
  permissions: string[]
  runtime_context: MetadataRecord
}

export interface UiRuntimeSummary {
  pages_count?: number
  layouts_count?: number
  components_count?: number
  source?: string
}

export function normalizeUiComponent(raw: unknown): UiComponent {
  const data = asRecord(raw)
  const bindingConfig = asRecord(data.binding_config ?? data.bindingConfig)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    component_key: asString(data.component_key ?? data.componentKey),
    name: asString(data.name, 'Component'),
    description:
      typeof data.description === 'string' ? data.description : null,
    component_type: asString(
      data.component_type ?? data.componentType,
      'custom',
    ),
    status: asString(data.status, 'active'),
    binding_type:
      typeof (data.binding_type ?? data.bindingType) === 'string'
        ? ((data.binding_type ?? data.bindingType) as string)
        : null,
    binding_config: bindingConfig,
    binding: normalizeUiComponentBinding(
      data.binding ?? {
        binding_type: data.binding_type ?? data.bindingType,
        module_key: bindingConfig.module_key ?? data.module_key,
        resource_key: bindingConfig.resource_key ?? data.resource_key,
        config: bindingConfig,
      },
    ),
    actions: asArray(data.actions).map(normalizeUiAction),
    conditions: asArray(data.conditions).map(normalizeUiCondition),
    metadata: asRecord(data.metadata),
    module_key:
      typeof (data.module_key ?? data.moduleKey) === 'string'
        ? ((data.module_key ?? data.moduleKey) as string)
        : null,
    region_key: asString(data.region_key ?? data.regionKey),
    sort_order: asNumber(data.sort_order ?? data.sortOrder),
    permission:
      typeof data.permission === 'string'
        ? data.permission
        : typeof data.required_permission === 'string'
          ? data.required_permission
          : null,
  }
}

export function normalizeUiComponentBinding(
  raw: unknown,
): UiComponentBinding {
  const data = asRecord(raw)

  return {
    binding_type: asString(data.binding_type ?? data.bindingType),
    public_id:
      typeof (data.public_id ?? data.publicId) === 'string'
        ? ((data.public_id ?? data.publicId) as string)
        : null,
    module_key:
      typeof (data.module_key ?? data.moduleKey) === 'string'
        ? ((data.module_key ?? data.moduleKey) as string)
        : null,
    resource_key:
      typeof (data.resource_key ?? data.resourceKey) === 'string'
        ? ((data.resource_key ?? data.resourceKey) as string)
        : null,
    config: asRecord(data.config),
  }
}

export function normalizeUiAction(raw: unknown): UiAction {
  const data = asRecord(raw)

  return {
    action_key: asString(data.action_key ?? data.actionKey),
    label: asString(data.label, 'Action'),
    action_type: asString(data.action_type ?? data.actionType),
    permission:
      typeof data.permission === 'string'
        ? data.permission
        : typeof data.required_permission === 'string'
          ? data.required_permission
          : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeUiCondition(raw: unknown): UiCondition {
  const data = asRecord(raw)

  return {
    condition_key: asString(data.condition_key ?? data.conditionKey),
    field: asString(data.field),
    operator: asString(data.operator),
    value: data.value,
    permission:
      typeof data.permission === 'string' ? data.permission : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeUiRegion(raw: unknown): UiRegion {
  const data = asRecord(raw)

  return {
    region_key: asString(data.region_key ?? data.regionKey, 'main'),
    region_type: asString(data.region_type ?? data.regionType, 'content'),
    label: asString(data.label, 'Region'),
    sort_order: asNumber(data.sort_order ?? data.sortOrder),
    components: asArray<string | UiComponent>(data.components),
    breakpoints: asArray(data.breakpoints),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeUiLayout(raw: unknown): UiLayout {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    layout_key: asString(data.layout_key ?? data.layoutKey, 'default'),
    name: asString(data.name, 'Layout'),
    description:
      typeof data.description === 'string' ? data.description : null,
    layout_type: asString(
      data.layout_type ?? data.layoutType,
      'single_column',
    ),
    status: asString(data.status, 'active'),
    regions: asArray(data.regions).map(normalizeUiRegion),
    breakpoints: asArray(data.breakpoints),
    metadata: asRecord(data.metadata),
    module_key:
      typeof (data.module_key ?? data.moduleKey) === 'string'
        ? ((data.module_key ?? data.moduleKey) as string)
        : null,
  }
}

export function normalizeUiPage(raw: unknown): UiPage {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    page_key: asString(data.page_key ?? data.pageKey),
    name: asString(data.name, 'Page'),
    description:
      typeof data.description === 'string' ? data.description : null,
    page_type: asString(data.page_type ?? data.pageType),
    status: asString(data.status, 'active'),
    module_key: asString(data.module_key ?? data.moduleKey),
    layout_key: asString(data.layout_key ?? data.layoutKey),
    permission:
      typeof data.permission === 'string'
        ? data.permission
        : typeof data.required_permission === 'string'
          ? data.required_permission
          : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeUiRenderPayload(raw: unknown): UiRenderPayload {
  const data = asRecord(raw)
  const permissions = asArray<unknown>(data.permissions).filter(
    (entry): entry is string => typeof entry === 'string',
  )

  return {
    page: normalizeUiPage(data.page),
    layout: normalizeUiLayout(data.layout),
    regions: asArray(data.regions).map(normalizeUiRegion),
    components: asArray(data.components).map(normalizeUiComponent),
    actions: asArray(data.actions).map(normalizeUiAction),
    conditions: asArray(data.conditions).map(normalizeUiCondition),
    breakpoints: asArray(data.breakpoints),
    theme: asRecord(data.theme),
    personalization: asRecord(data.personalization),
    permissions,
    runtime_context: asRecord(data.runtime_context ?? data.runtimeContext),
  }
}

export function isUiRenderPayloadEmpty(payload: UiRenderPayload | null): boolean {
  if (!payload) {
    return true
  }

  return (
    !payload.page.page_key &&
    payload.components.length === 0 &&
    payload.regions.length === 0
  )
}
