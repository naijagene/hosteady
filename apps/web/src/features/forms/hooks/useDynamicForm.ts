import { useMemo } from 'react'
import type { FormDefinition } from '@/api/types/forms'
import { applyFieldPermissions } from '../core/form-permissions'
import { normalizeFormDefinitionModel } from '../core/form-normalizer'
import { mergeFormDefaultValues } from '../core/form-defaults'
import { buildFormValidationRules } from '../core/form-validation'
import type { FormValues, NormalizedFormDefinition } from '../types'

export function useDynamicForm(options: {
  definition: FormDefinition
  permissions?: string[]
  initialValues?: FormValues
}) {
  const model = useMemo(
    () => normalizeFormDefinitionModel(options.definition),
    [options.definition],
  )

  const permissionAppliedFields = useMemo(
    () => applyFieldPermissions(model.fields, options.permissions ?? []),
    [model.fields, options.permissions],
  )

  const normalizedModel: NormalizedFormDefinition = useMemo(
    () => ({
      ...model,
      fields: permissionAppliedFields,
      sections: model.sections.map((section) => ({
        ...section,
        fields: permissionAppliedFields.filter((field) =>
          section.fields.some((item) => item.field_key === field.field_key),
        ),
      })),
      fieldMap: new Map(
        permissionAppliedFields.map((field) => [field.field_key, field]),
      ),
    }),
    [model, permissionAppliedFields],
  )

  const defaultValues = useMemo(
    () => mergeFormDefaultValues(normalizedModel, options.initialValues),
    [normalizedModel, options.initialValues],
  )

  const validationRules = useMemo(
    () =>
      buildFormValidationRules(
        normalizedModel.fields,
        normalizedModel.validationRules,
      ),
    [normalizedModel],
  )

  return {
    model: normalizedModel,
    defaultValues,
    validationRules,
  }
}
