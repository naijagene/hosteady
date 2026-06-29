import type { NormalizedFormDefinition, FormValues } from '../types'

function defaultForFieldType(fieldType: string): unknown {
  switch (fieldType) {
    case 'checkbox':
    case 'boolean':
    case 'switch':
      return false
    case 'multiselect':
      return []
    case 'number':
    case 'integer':
    case 'decimal':
      return ''
    default:
      return ''
  }
}

export function buildFormDefaultValues(model: NormalizedFormDefinition): FormValues {
  const defaults: FormValues = {}

  model.fields.forEach((field) => {
    if (field.default_value !== undefined && field.default_value !== null) {
      defaults[field.field_key] = field.default_value
      return
    }

    defaults[field.field_key] = defaultForFieldType(field.field_type)
  })

  return defaults
}

export function mergeFormDefaultValues(
  model: NormalizedFormDefinition,
  initialValues?: FormValues,
): FormValues {
  return {
    ...buildFormDefaultValues(model),
    ...(initialValues ?? {}),
  }
}
