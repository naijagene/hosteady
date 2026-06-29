import type { RegisterOptions } from 'react-hook-form'
import type { FormValidationRule } from '@/api/types/forms'
import type { NormalizedFormField } from '../types'

function ruleMessage(rule: FormValidationRule, fallback: string): string {
  return rule.message ?? fallback
}

function mergeValidate(
  rules: RegisterOptions,
  key: string,
  validator: FieldValidator,
): void {
  const existing =
    typeof rules.validate === 'object' && rules.validate !== null
      ? rules.validate
      : {}

  rules.validate = {
    ...existing,
    [key]: validator,
  }
}

type FieldValidator = (
  value: unknown,
  formValues: Record<string, unknown>,
) => boolean | string

function buildRuleValidator(rule: FormValidationRule): FieldValidator | undefined {
  const parameters = rule.parameters ?? {}

  switch (rule.rule) {
    case 'regex': {
      const pattern = String(parameters.pattern ?? parameters.value ?? '')
      if (!pattern) {
        return undefined
      }

      const expression = new RegExp(pattern)
      return (value: unknown) =>
        value === '' || value === undefined || value === null
          ? true
          : expression.test(String(value)) ||
            ruleMessage(rule, 'Invalid format.')
    }
    case 'in': {
      const allowed = Array.isArray(parameters.values)
        ? parameters.values.map(String)
        : Array.isArray(parameters.in)
          ? parameters.in.map(String)
          : []

      return (value: unknown) =>
        value === '' || value === undefined || value === null
          ? true
          : allowed.includes(String(value)) ||
            ruleMessage(rule, 'Invalid selection.')
    }
    case 'before':
    case 'after': {
      const compareValue = parameters.value ?? parameters.date
      return (value: unknown) => {
        if (!value) {
          return true
        }

        const current = new Date(String(value)).getTime()
        const compare = new Date(String(compareValue)).getTime()

        if (Number.isNaN(current) || Number.isNaN(compare)) {
          return ruleMessage(rule, 'Invalid date.')
        }

        if (rule.rule === 'before') {
          return current < compare || ruleMessage(rule, 'Date is too late.')
        }

        return current > compare || ruleMessage(rule, 'Date is too early.')
      }
    }
    default:
      return undefined
  }
}

export function buildFieldValidationRules(
  field: NormalizedFormField,
  extraRules: FormValidationRule[] = [],
): RegisterOptions {
  const rules: RegisterOptions = {}
  const fieldRules = [...(field.validation_rules ?? []), ...extraRules].filter(
    (rule) => rule.field === field.field_key || rule.field === '',
  )

  if (field.required) {
    rules.required = `${field.label} is required.`
  }

  fieldRules.forEach((rule) => {
    switch (rule.rule) {
      case 'required':
        rules.required = ruleMessage(rule, `${field.label} is required.`)
        break
      case 'email':
        rules.pattern = {
          value: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
          message: ruleMessage(rule, 'Enter a valid email address.'),
        }
        break
      case 'min':
        rules.min =
          typeof rule.parameters?.min === 'number'
            ? rule.parameters.min
            : Number(rule.parameters?.value ?? rule.parameters?.min)
        if (rules.min !== undefined && !Number.isNaN(rules.min)) {
          rules.min = Number(rules.min)
        }
        break
      case 'max':
        rules.max =
          typeof rule.parameters?.max === 'number'
            ? rule.parameters.max
            : Number(rule.parameters?.value ?? rule.parameters?.max)
        if (rules.max !== undefined && !Number.isNaN(rules.max)) {
          rules.max = Number(rules.max)
        }
        break
      case 'min_length':
        rules.minLength = {
          value: Number(rule.parameters?.min ?? rule.parameters?.value ?? 0),
          message: ruleMessage(rule, 'Value is too short.'),
        }
        break
      case 'max_length':
        rules.maxLength = {
          value: Number(rule.parameters?.max ?? rule.parameters?.value ?? 0),
          message: ruleMessage(rule, 'Value is too long.'),
        }
        break
      case 'integer':
      case 'numeric':
      case 'number':
        mergeValidate(rules, 'numeric', (value: unknown) => {
          if (value === '' || value === undefined || value === null) {
            return true
          }

          const numericValue = Number(value)
          if (Number.isNaN(numericValue)) {
            return ruleMessage(rule, 'Enter a valid number.')
          }

          if (rule.rule === 'integer' && !Number.isInteger(numericValue)) {
            return ruleMessage(rule, 'Enter a whole number.')
          }

          return true
        })
        break
      case 'boolean':
        mergeValidate(rules, 'boolean', (value: unknown) =>
          typeof value === 'boolean' ||
          value === '' ||
          value === undefined ||
          value === null ||
          ruleMessage(rule, 'Invalid boolean value.'),
        )
        break
      case 'date':
        mergeValidate(rules, 'date', (value: unknown) => {
          if (!value) {
            return true
          }

          return (
            !Number.isNaN(new Date(String(value)).getTime()) ||
            ruleMessage(rule, 'Enter a valid date.')
          )
        })
        break
      default: {
        const customValidator = buildRuleValidator(rule)
        if (customValidator) {
          mergeValidate(rules, rule.rule, customValidator)
        }
      }
    }
  })

  return rules
}

export function buildFormValidationRules(
  fields: NormalizedFormField[],
  globalRules: FormValidationRule[] = [],
): Record<string, RegisterOptions> {
  const result: Record<string, RegisterOptions> = {}

  fields.forEach((field) => {
    const scopedRules = globalRules.filter(
      (rule) => rule.field === field.field_key,
    )
    result[field.field_key] = buildFieldValidationRules(field, scopedRules)
  })

  return result
}
