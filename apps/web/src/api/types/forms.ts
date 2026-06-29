import {
  asArray,
  asBoolean,
  asNumber,
  asRecord,
  asString,
  type MetadataRecord,
} from './metadata-common'

export interface FormFieldOption {
  value: string
  label: string
  description?: string | null
  disabled?: boolean
  metadata?: MetadataRecord
}

export interface FormValidationRule {
  field: string
  rule: string
  message?: string | null
  parameters?: MetadataRecord
}

export interface FormCondition {
  key: string
  field: string
  operator: string
  value?: unknown
  target_type?: string | null
  target_key?: string | null
  metadata?: MetadataRecord
}

export interface FormAction {
  key: string
  label: string
  type: string
  handler?: string | null
  confirm_message?: string | null
  metadata?: MetadataRecord
}

export interface FormFieldPermission {
  required_permission?: string | null
  read_permission?: string | null
  write_permission?: string | null
  hidden_permission?: string | null
}

export interface FormField {
  field_key: string
  label: string
  field_type: string
  required?: boolean
  read_only?: boolean
  searchable?: boolean
  default_value?: unknown
  options?: FormFieldOption[]
  validation_rules?: FormValidationRule[]
  conditions?: FormCondition[]
  permissions?: FormFieldPermission
  section_key?: string | null
  tab_key?: string | null
  group_key?: string | null
  repeatable?: boolean
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
  entity_key?: string | null
  type?: string
  status?: string
  visibility?: string
  sections?: FormSection[]
  fields?: FormField[]
  actions?: FormAction[]
  conditions?: FormCondition[]
  validation_rules?: FormValidationRule[]
  metadata?: MetadataRecord
}

export interface FormBindingContext {
  moduleKey: string
  formKey: string
  source?: string
  page?: string
  binding?: string
  mode?: 'create' | 'edit' | 'readonly'
  submit_enabled?: boolean
  success_message?: string
  preserve_hidden?: boolean
  redirect_on_success?: string | null
  refresh_bindings_on_success?: boolean
}

export interface FormSubmissionPayload {
  values: MetadataRecord
  entity_key?: string | null
  entity_public_id?: string | null
  metadata?: MetadataRecord
}

export interface FormSubmissionResult {
  module_key: string
  form_key: string
  success: boolean
  status?: string
  submission_id?: string | null
  entity_public_id?: string | null
  values?: MetadataRecord
  warnings?: string[]
  metadata?: MetadataRecord
}

export interface FormSubmissionError {
  message: string
  field_errors: Record<string, string[]>
  status?: number | null
}

function normalizeFormFieldOption(raw: unknown): FormFieldOption {
  const data = asRecord(raw)
  return {
    value: asString(data.value),
    label: asString(data.label ?? data.name, 'Option'),
    description:
      typeof data.description === 'string' ? data.description : null,
    disabled: asBoolean(data.disabled),
    metadata: asRecord(data.metadata),
  }
}

function normalizeFormValidationRule(raw: unknown): FormValidationRule {
  const data = asRecord(raw)
  return {
    field: asString(data.field),
    rule: asString(data.rule),
    message: typeof data.message === 'string' ? data.message : null,
    parameters: asRecord(data.parameters),
  }
}

function normalizeFormCondition(raw: unknown): FormCondition {
  const data = asRecord(raw)
  return {
    key: asString(data.key ?? data.condition_key ?? data.conditionKey),
    field: asString(data.field),
    operator: asString(data.operator, 'equals'),
    value: data.value,
    target_type:
      typeof (data.target_type ?? data.targetType) === 'string'
        ? ((data.target_type ?? data.targetType) as string)
        : null,
    target_key:
      typeof (data.target_key ?? data.targetKey) === 'string'
        ? ((data.target_key ?? data.targetKey) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}

function normalizeFormAction(raw: unknown): FormAction {
  const data = asRecord(raw)
  return {
    key: asString(data.key ?? data.action_key ?? data.actionKey),
    label: asString(data.label, 'Action'),
    type: asString(data.type ?? data.action_type ?? data.actionType, 'submit'),
    handler: typeof data.handler === 'string' ? data.handler : null,
    confirm_message:
      typeof (data.confirm_message ?? data.confirmMessage) === 'string'
        ? ((data.confirm_message ?? data.confirmMessage) as string)
        : null,
    metadata: asRecord(data.metadata),
  }
}

function normalizeFormFieldPermissions(
  data: MetadataRecord,
): FormFieldPermission {
  return {
    required_permission:
      typeof (data.required_permission ?? data.requiredPermission) === 'string'
        ? ((data.required_permission ?? data.requiredPermission) as string)
        : null,
    read_permission:
      typeof (data.read_permission ?? data.readPermission) === 'string'
        ? ((data.read_permission ?? data.readPermission) as string)
        : null,
    write_permission:
      typeof (data.write_permission ?? data.writePermission) === 'string'
        ? ((data.write_permission ?? data.writePermission) as string)
        : null,
    hidden_permission:
      typeof (data.hidden_permission ?? data.hiddenPermission) === 'string'
        ? ((data.hidden_permission ?? data.hiddenPermission) as string)
        : null,
  }
}

export function normalizeFormField(raw: unknown): FormField {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  const permissions = normalizeFormFieldPermissions({
    ...metadata,
    ...data,
  })

  return {
    field_key: asString(data.field_key ?? data.fieldKey ?? data.key),
    label: asString(data.label ?? data.name, 'Field'),
    field_type: asString(
      data.field_type ?? data.fieldType ?? data.type,
      'text',
    ),
    required: asBoolean(data.required),
    read_only: asBoolean(data.read_only ?? data.readOnly ?? data.readonly),
    searchable: asBoolean(data.searchable),
    default_value: data.default_value ?? data.defaultValue ?? data.value,
    options: asArray(data.options).map(normalizeFormFieldOption),
    validation_rules: asArray(
      data.validation_rules ?? data.validationRules,
    ).map(normalizeFormValidationRule),
    conditions: asArray(data.conditions).map(normalizeFormCondition),
    permissions,
    section_key:
      typeof (data.section_key ?? data.sectionKey) === 'string'
        ? ((data.section_key ?? data.sectionKey) as string)
        : null,
    tab_key:
      typeof (data.tab_key ?? data.tabKey) === 'string'
        ? ((data.tab_key ?? data.tabKey) as string)
        : null,
    group_key:
      typeof (data.group_key ?? data.groupKey) === 'string'
        ? ((data.group_key ?? data.groupKey) as string)
        : null,
    repeatable: asBoolean(data.repeatable),
    metadata,
  }
}

export function normalizeFormDefinition(raw: unknown): FormDefinition {
  const data = asRecord(raw)

  return {
    public_id: asString(data.public_id ?? data.publicId),
    module_key: asString(data.module_key ?? data.moduleKey),
    form_key: asString(data.form_key ?? data.formKey ?? data.key),
    name: asString(data.name ?? data.label, 'Form'),
    description:
      typeof data.description === 'string' ? data.description : null,
    entity_key:
      typeof (data.entity_key ?? data.entityKey) === 'string'
        ? ((data.entity_key ?? data.entityKey) as string)
        : null,
    type: asString(data.type),
    status: asString(data.status, 'active'),
    visibility: asString(data.visibility),
    sections: asArray(data.sections).map((section) => {
      const item = asRecord(section)
      return {
        section_key: asString(item.section_key ?? item.sectionKey ?? item.key),
        label: asString(item.label, 'Section'),
        sort_order: asNumber(item.sort_order ?? item.sortOrder),
        fields: asArray<string>(
          item.fields ?? item.field_keys ?? item.fieldKeys,
        ).map((field) => asString(field)),
        metadata: asRecord(item.metadata),
      }
    }),
    fields: asArray(data.fields).map(normalizeFormField),
    actions: asArray(data.actions).map(normalizeFormAction),
    conditions: asArray(data.conditions).map(normalizeFormCondition),
    validation_rules: asArray(
      data.validation_rules ?? data.validationRules,
    ).map(normalizeFormValidationRule),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeFormSubmissionResult(raw: unknown): FormSubmissionResult {
  const data = asRecord(raw)

  return {
    module_key: asString(data.module_key ?? data.moduleKey),
    form_key: asString(data.form_key ?? data.formKey),
    success: asBoolean(data.success),
    status: asString(data.status),
    submission_id: asString(data.submission_id ?? data.submissionId) || null,
    entity_public_id:
      asString(data.entity_public_id ?? data.entityPublicId) || null,
    values: asRecord(data.values ?? data.data),
    warnings: asArray<unknown>(data.warnings)
      .filter((entry): entry is string => typeof entry === 'string'),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeFormBindingContext(
  raw: MetadataRecord | undefined,
  moduleKey: string,
  formKey: string,
): FormBindingContext {
  const config = asRecord(raw)

  const modeValue = asString(config.mode)
  const mode =
    modeValue === 'edit' || modeValue === 'readonly' || modeValue === 'create'
      ? modeValue
      : undefined

  return {
    moduleKey,
    formKey,
    source: asString(config.source, 'web') || 'web',
    page: asString(config.page),
    binding: asString(config.binding),
    mode,
    submit_enabled: config.submit_enabled !== false && config.submitEnabled !== false,
    success_message: asString(config.success_message ?? config.successMessage),
    preserve_hidden:
      config.preserve_hidden === true || config.preserveHidden === true,
    redirect_on_success:
      typeof (config.redirect_on_success ?? config.redirectOnSuccess) ===
      'string'
        ? ((config.redirect_on_success ?? config.redirectOnSuccess) as string)
        : null,
    refresh_bindings_on_success:
      config.refresh_bindings_on_success === true ||
      config.refreshBindingsOnSuccess === true,
  }
}
