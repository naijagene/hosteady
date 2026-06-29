import { Controller } from 'react-hook-form'
import type { BaseFieldProps } from './basic-fields'
import { FieldShell } from './field-shell'
import { fieldAriaProps, inputClassName } from './field-utils'

export function SelectField({
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
      <select
        {...register(field.field_key, rules)}
        {...fieldAriaProps(field.field_key, error)}
        className={inputClassName}
        disabled={disabled || readOnly}
      >
        <option value="">Select…</option>
        {(field.options ?? []).map((option) => (
          <option key={option.value} value={option.value} disabled={option.disabled}>
            {option.label}
          </option>
        ))}
      </select>
    </FieldShell>
  )
}

export function MultiSelectField({
  field,
  control,
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
      <Controller
        name={field.field_key}
        control={control}
        rules={rules}
        render={({ field: controllerField }) => (
          <select
            multiple
            {...controllerField}
            value={Array.isArray(controllerField.value) ? controllerField.value.map(String) : []}
            onChange={(event) => {
              const values = Array.from(event.target.selectedOptions).map(
                (option) => option.value,
              )
              controllerField.onChange(values)
            }}
            {...fieldAriaProps(field.field_key, error)}
            className={`${inputClassName} min-h-28`}
            disabled={disabled || readOnly}
          >
            {(field.options ?? []).map((option) => (
              <option key={option.value} value={option.value} disabled={option.disabled}>
                {option.label}
              </option>
            ))}
          </select>
        )}
      />
    </FieldShell>
  )
}

export function CheckboxField({
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
      <label className="inline-flex items-center gap-2 text-sm text-foreground">
        <input
          type="checkbox"
          {...register(field.field_key, rules)}
          {...fieldAriaProps(field.field_key, error)}
          disabled={disabled || readOnly}
        />
        <span>{String(field.metadata?.checkbox_label ?? field.label)}</span>
      </label>
    </FieldShell>
  )
}

export function RadioField({
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
      <div className="space-y-2">
        {(field.options ?? []).map((option) => (
          <label
            key={option.value}
            className="flex items-center gap-2 text-sm text-foreground"
          >
            <input
              type="radio"
              value={option.value}
              {...register(field.field_key, rules)}
              disabled={disabled || readOnly || option.disabled}
            />
            <span>{option.label}</span>
          </label>
        ))}
      </div>
    </FieldShell>
  )
}

export function SwitchField({
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
      <label className="inline-flex items-center gap-2 text-sm text-foreground">
        <input
          type="checkbox"
          role="switch"
          {...register(field.field_key, rules)}
          {...fieldAriaProps(field.field_key, error)}
          disabled={disabled || readOnly}
        />
        <span>{String(field.metadata?.switch_label ?? 'Enabled')}</span>
      </label>
    </FieldShell>
  )
}

export function HiddenField({ field, register, rules }: BaseFieldProps) {
  return <input type="hidden" {...register(field.field_key, rules)} />
}

export function ReadonlyField({ field }: Pick<BaseFieldProps, 'field'>) {
  return (
    <FieldShell fieldKey={field.field_key} label={field.label}>
      <div className="rounded-md border border-border bg-muted/20 px-3 py-2 text-sm text-muted-foreground">
        {String(field.default_value ?? field.metadata?.display_value ?? '—')}
      </div>
    </FieldShell>
  )
}

export function UnsupportedField({ field }: Pick<BaseFieldProps, 'field'>) {
  return (
    <FieldShell fieldKey={field.field_key} label={field.label}>
      <div
        className="rounded-md border border-dashed border-border bg-muted/20 px-3 py-2 text-xs text-muted-foreground"
        data-testid="unsupported-field"
      >
        Unsupported field type: {field.field_type}
      </div>
    </FieldShell>
  )
}
