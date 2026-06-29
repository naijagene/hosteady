import { createElement, type ComponentType } from 'react'
import type { BaseFieldProps } from './basic-fields'
import {
  CheckboxField,
  HiddenField,
  MultiSelectField,
  RadioField,
  ReadonlyField,
  SelectField,
  SwitchField,
  UnsupportedField,
} from './choice-fields'
import { DocumentField, FileField } from './document-fields'
import {
  DateField,
  DateTimeField,
  EmailField,
  NumberField,
  PasswordField,
  TextField,
  TextareaField,
  TimeField,
} from './basic-fields'

type FieldComponent = ComponentType<BaseFieldProps>

const fieldRegistry: Record<string, FieldComponent> = {
  text: TextField,
  string: TextField,
  textarea: TextareaField,
  number: NumberField,
  integer: NumberField,
  decimal: NumberField,
  email: EmailField,
  password: PasswordField,
  date: DateField,
  datetime: DateTimeField,
  time: TimeField,
  select: SelectField,
  enum: SelectField,
  multiselect: MultiSelectField,
  checkbox: CheckboxField,
  boolean: CheckboxField,
  radio: RadioField,
  switch: SwitchField,
  file: FileField,
  document: DocumentField,
  reference: DocumentField,
  hidden: HiddenField,
  readonly: ReadonlyField,
}

export function resolveFieldComponent(fieldType: string): FieldComponent {
  return fieldRegistry[fieldType.toLowerCase()] ?? UnsupportedField
}

export function renderFieldComponent(props: BaseFieldProps) {
  return createElement(resolveFieldComponent(props.field.field_type), props)
}

export {
  TextField,
  TextareaField,
  NumberField,
  EmailField,
  PasswordField,
  DateField,
  DateTimeField,
  TimeField,
  SelectField,
  MultiSelectField,
  CheckboxField,
  RadioField,
  SwitchField,
  HiddenField,
  ReadonlyField,
  FileField,
  DocumentField,
  UnsupportedField,
}
