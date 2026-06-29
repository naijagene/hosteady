import type { Control, RegisterOptions, UseFormRegister } from 'react-hook-form'
import type { NormalizedFormField } from '../types'
import { FieldShell } from './field-shell'
import { fieldAriaProps, inputClassName } from './field-utils'

export interface BaseFieldProps {
  field: NormalizedFormField
  register: UseFormRegister<Record<string, unknown>>
  control: Control<Record<string, unknown>>
  rules?: RegisterOptions
  error?: string
  disabled?: boolean
  readOnly?: boolean
}

export function TextField({
  field,
  register,
  rules,
  error,
  disabled,
  readOnly,
}: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={field.field_key}
      label={field.label}
      required={field.required}
      error={error}
    >
      <input
        type="text"
        {...register(field.field_key, rules)}
        {...fieldAriaProps(field.field_key, error)}
        className={inputClassName}
        disabled={disabled}
        readOnly={readOnly}
      />
    </FieldShell>
  )
}

export function TextareaField({
  field,
  register,
  rules,
  error,
  disabled,
  readOnly,
}: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={field.field_key}
      label={field.label}
      required={field.required}
      error={error}
    >
      <textarea
        {...register(field.field_key, rules)}
        {...fieldAriaProps(field.field_key, error)}
        className={`${inputClassName} min-h-24`}
        disabled={disabled}
        readOnly={readOnly}
      />
    </FieldShell>
  )
}

export function NumberField({
  field,
  register,
  rules,
  error,
  disabled,
  readOnly,
}: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={field.field_key}
      label={field.label}
      required={field.required}
      error={error}
    >
      <input
        type="number"
        {...register(field.field_key, rules)}
        {...fieldAriaProps(field.field_key, error)}
        className={inputClassName}
        disabled={disabled}
        readOnly={readOnly}
      />
    </FieldShell>
  )
}

export function EmailField(props: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={props.field.field_key}
      label={props.field.label}
      required={props.field.required}
      error={props.error}
    >
      <input
        type="email"
        {...props.register(props.field.field_key, props.rules)}
        {...fieldAriaProps(props.field.field_key, props.error)}
        className={inputClassName}
        disabled={props.disabled}
        readOnly={props.readOnly}
      />
    </FieldShell>
  )
}

export function PasswordField(props: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={props.field.field_key}
      label={props.field.label}
      required={props.field.required}
      error={props.error}
    >
      <input
        type="password"
        autoComplete="new-password"
        {...props.register(props.field.field_key, props.rules)}
        {...fieldAriaProps(props.field.field_key, props.error)}
        className={inputClassName}
        disabled={props.disabled}
        readOnly={props.readOnly}
      />
    </FieldShell>
  )
}

export function DateField(props: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={props.field.field_key}
      label={props.field.label}
      required={props.field.required}
      error={props.error}
    >
      <input
        type="date"
        {...props.register(props.field.field_key, props.rules)}
        {...fieldAriaProps(props.field.field_key, props.error)}
        className={inputClassName}
        disabled={props.disabled}
        readOnly={props.readOnly}
      />
    </FieldShell>
  )
}

export function DateTimeField(props: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={props.field.field_key}
      label={props.field.label}
      required={props.field.required}
      error={props.error}
    >
      <input
        type="datetime-local"
        {...props.register(props.field.field_key, props.rules)}
        {...fieldAriaProps(props.field.field_key, props.error)}
        className={inputClassName}
        disabled={props.disabled}
        readOnly={props.readOnly}
      />
    </FieldShell>
  )
}

export function TimeField(props: BaseFieldProps) {
  return (
    <FieldShell
      fieldKey={props.field.field_key}
      label={props.field.label}
      required={props.field.required}
      error={props.error}
    >
      <input
        type="time"
        {...props.register(props.field.field_key, props.rules)}
        {...fieldAriaProps(props.field.field_key, props.error)}
        className={inputClassName}
        disabled={props.disabled}
        readOnly={props.readOnly}
      />
    </FieldShell>
  )
}
