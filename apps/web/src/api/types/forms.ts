import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface FormField {
  field_key: string
  label: string
  field_type: string
  required?: boolean
  read_only?: boolean
  default_value?: unknown
  metadata?: MetadataRecord
}

export interface FormSection {
  section_key: string
  label: string
  sort_order?: number
  fields?: string[]
  metadata?: MetadataRecord
}

export interface FormDefinition {
  public_id?: string
  module_key: string
  form_key: string
  name: string
  description?: string | null
  status?: string
  sections?: FormSection[]
  fields?: FormField[]
  metadata?: MetadataRecord
}

export interface FormSubmissionPayload {
  values: MetadataRecord
  metadata?: MetadataRecord
}

export function normalizeFormDefinition(raw: unknown): FormDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    form_key: asString(data.form_key ?? data.formKey),
    name: asString(data.name, 'Form'),
    description:
      typeof data.description === 'string' ? data.description : null,
    status: asString(data.status, 'active'),
    sections: asArray(data.sections).map((section) => {
      const item = asRecord(section)
      return {
        section_key: asString(item.section_key ?? item.sectionKey),
        label: asString(item.label, 'Section'),
        sort_order: typeof item.sort_order === 'number' ? item.sort_order : 0,
        fields: asArray<string>(item.fields),
        metadata: asRecord(item.metadata),
      }
    }),
    fields: asArray(data.fields).map((field) => {
      const item = asRecord(field)
      return {
        field_key: asString(item.field_key ?? item.fieldKey),
        label: asString(item.label, 'Field'),
        field_type: asString(item.field_type ?? item.fieldType, 'text'),
        required: item.required === true,
        read_only: item.read_only === true || item.readOnly === true,
        default_value: item.default_value ?? item.defaultValue,
        metadata: asRecord(item.metadata),
      }
    }),
    metadata: asRecord(data.metadata),
  }
}
