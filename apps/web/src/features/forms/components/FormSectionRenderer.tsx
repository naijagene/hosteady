import type { Control, FieldErrors, RegisterOptions, UseFormRegister } from 'react-hook-form'
import type { NormalizedFormSection } from '../types'
import { FormFieldRenderer } from './FormFieldRenderer'

interface FormSectionRendererProps {
  section: NormalizedFormSection
  register: UseFormRegister<Record<string, unknown>>
  control: Control<Record<string, unknown>>
  errors: FieldErrors<Record<string, unknown>>
  validationRules: Record<string, RegisterOptions>
  isFieldVisible: (fieldKey: string) => boolean
  isFieldEnabled: (fieldKey: string) => boolean
  readOnly?: boolean
}

export function FormSectionRenderer({
  section,
  register,
  control,
  errors,
  validationRules,
  isFieldVisible,
  isFieldEnabled,
  readOnly = false,
}: FormSectionRendererProps) {
  const visibleFields = section.fields.filter(
    (field) => isFieldVisible(field.field_key) || field.field_type === 'hidden',
  )

  if (visibleFields.length === 0) {
    return null
  }

  return (
    <section className="space-y-4" data-section-key={section.section_key}>
      {section.label ? (
        <h3 className="text-sm font-semibold text-foreground">{section.label}</h3>
      ) : null}
      <div className="space-y-4">
        {visibleFields.map((field) => (
          <FormFieldRenderer
            key={field.field_key}
            field={field}
            register={register}
            control={control}
            errors={errors}
            rules={validationRules[field.field_key]}
            visible={isFieldVisible(field.field_key) || field.field_type === 'hidden'}
            enabled={isFieldEnabled(field.field_key)}
            readOnly={readOnly}
          />
        ))}
      </div>
    </section>
  )
}
