import type { UiComponent, UiRegion } from '@/api/types/ui'
import { asRecord, asString } from '@/api/types/metadata-common'

export function hasPermission(
  permissions: string[],
  required?: string | null,
): boolean {
  if (!required) {
    return true
  }

  return permissions.includes(required)
}

export function resolveComponentByReference(
  reference: string | UiComponent,
  index: Map<string, UiComponent>,
): UiComponent | null {
  if (typeof reference !== 'string') {
    return reference
  }

  return index.get(reference) ?? null
}

export function sortRegions(regions: UiRegion[]): UiRegion[] {
  return [...regions].sort((left, right) => left.sort_order - right.sort_order)
}

export function sortComponents(components: UiComponent[]): UiComponent[] {
  return [...components].sort(
    (left, right) => (left.sort_order ?? 0) - (right.sort_order ?? 0),
  )
}

export function getRegionComponents(
  region: UiRegion,
  allComponents: UiComponent[],
): UiComponent[] {
  const index = new Map<string, UiComponent>()

  allComponents.forEach((component) => {
    if (component.public_id) {
      index.set(component.public_id, component)
    }
    if (component.component_key) {
      index.set(component.component_key, component)
    }
  })

  const resolved = region.components
    .map((reference) => resolveComponentByReference(reference, index))
    .filter((component): component is UiComponent => component !== null)

  if (resolved.length > 0) {
    return sortComponents(resolved)
  }

  return sortComponents(
    allComponents.filter(
      (component) =>
        component.region_key === region.region_key ||
        asString(component.metadata?.region_key) === region.region_key,
    ),
  )
}

export function safeMetadataClasses(metadata: Record<string, unknown> | undefined): string {
  const classes = metadata?.className ?? metadata?.class_name ?? metadata?.css_class

  if (typeof classes !== 'string') {
    return ''
  }

  return classes
    .split(/\s+/)
    .filter((token) => /^[a-zA-Z0-9_\-:/[\].]+$/.test(token))
    .join(' ')
}

export function getLayoutClassName(layoutType: string): string {
  switch (layoutType) {
    case 'single_column':
      return 'renderer-layout renderer-layout--single-column grid grid-cols-1 gap-4'
    case 'two_column':
      return 'renderer-layout renderer-layout--two-column grid gap-4 md:grid-cols-2'
    case 'three_column':
      return 'renderer-layout renderer-layout--three-column grid gap-4 md:grid-cols-3'
    case 'sidebar':
      return 'renderer-layout renderer-layout--sidebar grid gap-4 md:grid-cols-[16rem_1fr]'
    case 'header_content':
      return 'renderer-layout renderer-layout--header-content flex flex-col gap-4'
    case 'dashboard_grid':
      return 'renderer-layout renderer-layout--dashboard-grid grid gap-4 sm:grid-cols-2 xl:grid-cols-3'
    case 'tabbed':
      return 'renderer-layout renderer-layout--tabbed flex flex-col gap-4'
    case 'wizard':
      return 'renderer-layout renderer-layout--wizard flex flex-col gap-4'
    case 'split_pane':
      return 'renderer-layout renderer-layout--split-pane grid gap-4 md:grid-cols-2'
    default:
      return 'renderer-layout renderer-layout--custom flex flex-col gap-4'
  }
}

export function getRegionClassName(regionType: string): string {
  switch (regionType) {
    case 'header':
      return 'renderer-region renderer-region--header'
    case 'sidebar':
      return 'renderer-region renderer-region--sidebar'
    case 'footer':
      return 'renderer-region renderer-region--footer'
    case 'aside':
      return 'renderer-region renderer-region--aside'
    default:
      return 'renderer-region renderer-region--content'
  }
}

export function mergePermissions(
  runtimePermissions: string[],
  payloadPermissions: string[] = [],
): string[] {
  return Array.from(new Set([...runtimePermissions, ...payloadPermissions]))
}

export function extractStaticText(component: UiComponent): string {
  const metadata = asRecord(component.metadata)
  return asString(
    metadata.text ?? metadata.content ?? component.description ?? component.name,
  )
}

export function extractMetricValue(component: UiComponent): string {
  const metadata = asRecord(component.metadata)
  return asString(metadata.value ?? metadata.metric_value, '—')
}
