import type { FormFieldPermission } from '@/api/types/forms'
import type { NormalizedFormField } from '../types'

export interface FieldPermissionState {
  hidden: boolean
  readonly: boolean
}

function hasPermission(permissions: string[], required?: string | null): boolean {
  if (!required) {
    return true
  }

  return permissions.includes(required)
}

export function resolveFieldPermissionState(
  field: NormalizedFormField,
  permissions: string[],
): FieldPermissionState {
  const fieldPermissions: FormFieldPermission = field.permissions ?? {}

  if (
    fieldPermissions.hidden_permission &&
    hasPermission(permissions, fieldPermissions.hidden_permission)
  ) {
    return { hidden: true, readonly: true }
  }

  if (
    fieldPermissions.read_permission &&
    !hasPermission(permissions, fieldPermissions.read_permission)
  ) {
    return { hidden: true, readonly: true }
  }

  if (
    fieldPermissions.write_permission &&
    !hasPermission(permissions, fieldPermissions.write_permission)
  ) {
    return { hidden: false, readonly: true }
  }

  if (
    fieldPermissions.required_permission &&
    !hasPermission(permissions, fieldPermissions.required_permission)
  ) {
    return { hidden: true, readonly: true }
  }

  return {
    hidden: false,
    readonly: field.readonly || field.read_only === true,
  }
}

export function applyFieldPermissions(
  fields: NormalizedFormField[],
  permissions: string[],
): NormalizedFormField[] {
  return fields.map((field) => {
    const state = resolveFieldPermissionState(field, permissions)

    return {
      ...field,
      visible: !state.hidden,
      readonly: state.readonly,
      enabled: !state.readonly,
    }
  })
}
