import type { FormDefinition, FormField } from '@/api/types/forms'
import type { NormalizedFormDefinition, NormalizedFormField, NormalizedFormSection } from '../types'

function sortSections<T extends { sort_order?: number }>(items: T[]): T[] {
  return [...items].sort(
    (left, right) => (left.sort_order ?? 0) - (right.sort_order ?? 0),
  )
}

function createNormalizedField(field: FormField): NormalizedFormField {
  return {
    ...field,
    visible: true,
    enabled: true,
    hidden: field.field_type === 'hidden',
    readonly: field.read_only === true || field.field_type === 'readonly',
  }
}

export function normalizeFormDefinitionModel(
  definition: FormDefinition,
): NormalizedFormDefinition {
  const fields = (definition.fields ?? []).map(createNormalizedField)
  const fieldMap = new Map(fields.map((field) => [field.field_key, field]))

  const sections: NormalizedFormSection[] = sortSections(
    definition.sections ?? [],
  ).map((section) => {
    const sectionFields =
      section.fields && section.fields.length > 0
        ? section.fields
            .map((key) => fieldMap.get(key))
            .filter((field): field is NormalizedFormField => Boolean(field))
        : fields.filter((field) => field.section_key === section.section_key)

    return {
      ...section,
      fields: sectionFields,
    }
  })

  const orphanFields = fields.filter(
    (field) =>
      !sections.some((section) =>
        section.fields.some((item) => item.field_key === field.field_key),
      ),
  )

  if (orphanFields.length > 0) {
    sections.push({
      section_key: 'default',
      label: '',
      sort_order: 999,
      fields: orphanFields,
    })
  }

  return {
    definition,
    sections,
    fields,
    fieldMap,
    actions: definition.actions ?? [],
    conditions: definition.conditions ?? [],
    validationRules: [
      ...(definition.validation_rules ?? []),
      ...fields.flatMap((field) => field.validation_rules ?? []),
    ],
  }
}

export function getFieldByKey(
  model: NormalizedFormDefinition,
  fieldKey: string,
): NormalizedFormField | undefined {
  return model.fieldMap.get(fieldKey)
}
