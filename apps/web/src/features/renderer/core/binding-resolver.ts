import type { UiComponent } from '@/api/types/ui'
import { asRecord, asString } from '@/api/types/metadata-common'

export interface ResolvedBinding {
  bindingType: string
  moduleKey: string
  resourceKey: string
  config: Record<string, unknown>
}

export function resolveComponentBinding(
  component: UiComponent,
  fallbackModuleKey?: string,
): ResolvedBinding | null {
  const binding = component.binding
  const bindingConfig = component.binding_config ?? {}
  const bindingType = asString(
    binding?.binding_type ?? component.binding_type ?? component.component_type,
  )

  if (!bindingType) {
    return null
  }

  const moduleKey = asString(
    binding?.module_key ??
      bindingConfig.module_key ??
      component.module_key ??
      fallbackModuleKey,
  )
  const resourceKey = asString(
    binding?.resource_key ??
      bindingConfig.resource_key ??
      bindingConfig.form_key ??
      bindingConfig.table_key ??
      bindingConfig.dashboard_key ??
      bindingConfig.report_key ??
      component.component_key,
  )

  if (!moduleKey || !resourceKey) {
    return null
  }

  return {
    bindingType: bindingType.toLowerCase(),
    moduleKey,
    resourceKey,
    config: {
      ...asRecord(binding?.config),
      ...asRecord(bindingConfig),
    },
  }
}

export function isBindingType(
  binding: ResolvedBinding | null,
  ...types: string[]
): binding is ResolvedBinding {
  if (!binding) {
    return false
  }

  return types.some((type) => binding.bindingType === type.toLowerCase())
}

export function bindingQueryEnabled(binding: ResolvedBinding | null): boolean {
  if (!binding) {
    return false
  }

  return binding.config.query_enabled === true || binding.config.auto_query === true
}
