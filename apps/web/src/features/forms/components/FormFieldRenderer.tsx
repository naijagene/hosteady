import type { Control, FieldErrors, RegisterOptions, UseFormRegister } from 'react-hook-form'
import type { NormalizedFormField } from '../types'
import { renderFieldComponent } from '../fields'

interface FormFieldRendererProps {
  field: NormalizedFormField
  register: UseFormRegister<Record<string, unknown>>
  control: Control<Record<string, unknown>>
  errors: FieldErrors<Record<string, unknown>>
  visible?: boolean
  enabled?: boolean
  readOnly?: boolean
  rules?: RegisterOptions
}

export function FormFieldRenderer({
  field,
  register,
  control,
  errors,
  visible = true,
  enabled = true,
  readOnly = false,
  rules,
}: FormFieldRendererProps) {
  if (!visible && field.field_type !== 'hidden') {
    return null
  }

  const error = errors[field.field_key]?.message as string | undefined

  return renderFieldComponent({
    field,
    register,
    control,
    rules,
    error,
    disabled: !enabled,
    readOnly: readOnly || field.readonly,
  })
}
