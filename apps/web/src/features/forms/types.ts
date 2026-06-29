import type {
  FormCondition,
  FormDefinition,
  FormField,
  FormSection,
  FormValidationRule,
} from '@/api/types/forms'

export interface NormalizedFormField extends FormField {
  visible: boolean
  enabled: boolean
  hidden: boolean
  readonly: boolean
}

export interface NormalizedFormSection {
  section_key: string
  label: string
  sort_order?: number
  metadata?: FormSection['metadata']
  fields: NormalizedFormField[]
}

export interface NormalizedFormDefinition {
  definition: FormDefinition
  sections: NormalizedFormSection[]
  fields: NormalizedFormField[]
  fieldMap: Map<string, NormalizedFormField>
  actions: FormDefinition['actions']
  conditions: FormCondition[]
  validationRules: FormValidationRule[]
}

export type FormValues = Record<string, unknown>

export type FieldVisibilityMap = Record<string, boolean>
export type FieldEnabledMap = Record<string, boolean>
