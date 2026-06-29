import type { FormBindingContext } from '@/api/types/forms'
import type { FormValues, NormalizedFormDefinition } from '../types'
import { evaluateConditions } from './form-conditions'

export function buildSubmissionPayload(
  values: FormValues,
  model: NormalizedFormDefinition,
  options: {
    binding?: FormBindingContext
    preserveHidden?: boolean
    visibleFieldKeys: Set<string>
  },
): FormValues {
  const payload: FormValues = {}

  model.fields.forEach((field) => {
    const isVisible = options.visibleFieldKeys.has(field.field_key)
    const includeHidden = options.preserveHidden === true

    if (!isVisible && field.field_type !== 'hidden' && !includeHidden) {
      return
    }

    if (
      !isVisible &&
      !includeHidden &&
      !evaluateConditions(field.conditions ?? [], values)
    ) {
      return
    }

    payload[field.field_key] = values[field.field_key]
  })

  return payload
}

export function buildSubmissionMetadata(
  binding?: FormBindingContext,
): Record<string, unknown> {
  return {
    source: binding?.source ?? 'web',
    page: binding?.page,
    binding: binding?.binding,
  }
}
