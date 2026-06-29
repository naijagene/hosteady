import { useMemo } from 'react'
import type { FormCondition } from '@/api/types/forms'
import {
  evaluateConditions,
  resolveTargetEnabled,
  resolveTargetVisibility,
} from '../core/form-conditions'
import type { FormValues, NormalizedFormField } from '../types'

export function useFormConditions(options: {
  fields: NormalizedFormField[]
  formConditions: FormCondition[]
  values: FormValues
}) {
  return useMemo(() => {
    const visibleFieldKeys = new Set<string>()
    const enabledFieldKeys = new Set<string>()

    options.fields.forEach((field) => {
      if (!field.visible) {
        return
      }

      const fieldVisible = resolveTargetVisibility(
        [...options.formConditions, ...(field.conditions ?? [])],
        options.values,
        field.field_key,
      )

      const fieldEnabled = resolveTargetEnabled(
        [...options.formConditions, ...(field.conditions ?? [])],
        options.values,
        field.field_key,
      )

      if (field.field_type === 'hidden') {
        visibleFieldKeys.add(field.field_key)
        enabledFieldKeys.add(field.field_key)
        return
      }

      if (fieldVisible && evaluateConditions(field.conditions ?? [], options.values)) {
        visibleFieldKeys.add(field.field_key)
      }

      if (fieldEnabled) {
        enabledFieldKeys.add(field.field_key)
      }
    })

    return {
      visibleFieldKeys,
      enabledFieldKeys,
      isFieldVisible: (fieldKey: string) => visibleFieldKeys.has(fieldKey),
      isFieldEnabled: (fieldKey: string) => enabledFieldKeys.has(fieldKey),
    }
  }, [options.fields, options.formConditions, options.values])
}
