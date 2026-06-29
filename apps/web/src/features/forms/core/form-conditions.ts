import type { FormCondition } from '@/api/types/forms'
import type { FormValues } from '../types'

function normalizeOperator(operator: string): string {
  return operator.toLowerCase().replace(/\s+/g, '_')
}

function toArrayValue(value: unknown): unknown[] {
  return Array.isArray(value) ? value : [value]
}

export function evaluateCondition(
  condition: FormCondition,
  values: FormValues,
): boolean {
  const left = values[condition.field]
  const right = condition.value
  const operator = normalizeOperator(condition.operator)

  switch (operator) {
    case 'equals':
    case 'eq':
      return String(left ?? '') === String(right ?? '')
    case 'not_equals':
    case 'neq':
      return String(left ?? '') !== String(right ?? '')
    case 'contains':
      return String(left ?? '').includes(String(right ?? ''))
    case 'not_contains':
      return !String(left ?? '').includes(String(right ?? ''))
    case 'greater_than':
    case 'gt':
      return Number(left) > Number(right)
    case 'less_than':
    case 'lt':
      return Number(left) < Number(right)
    case 'is_empty':
      return (
        left === undefined ||
        left === null ||
        left === '' ||
        (Array.isArray(left) && left.length === 0)
      )
    case 'is_not_empty':
      return !(
        left === undefined ||
        left === null ||
        left === '' ||
        (Array.isArray(left) && left.length === 0)
      )
    case 'in':
      return toArrayValue(right).map(String).includes(String(left ?? ''))
    case 'not_in':
      return !toArrayValue(right).map(String).includes(String(left ?? ''))
    default:
      return true
  }
}

export function evaluateConditions(
  conditions: FormCondition[],
  values: FormValues,
): boolean {
  if (conditions.length === 0) {
    return true
  }

  return conditions.every((condition) => evaluateCondition(condition, values))
}

export function resolveTargetVisibility(
  conditions: FormCondition[],
  values: FormValues,
  targetKey: string,
): boolean {
  const scoped = conditions.filter(
    (condition) =>
      condition.target_key === targetKey ||
      condition.metadata?.target_field === targetKey,
  )

  if (scoped.length === 0) {
    return true
  }

  return evaluateConditions(scoped, values)
}

export function resolveTargetEnabled(
  conditions: FormCondition[],
  values: FormValues,
  targetKey: string,
): boolean {
  const scoped = conditions.filter((condition) => {
    const targetType = condition.target_type ?? condition.metadata?.target_type
    return (
      (condition.target_key === targetKey ||
        condition.metadata?.target_field === targetKey) &&
      (targetType === 'enable' ||
        targetType === 'enabled' ||
        targetType === 'field')
    )
  })

  if (scoped.length === 0) {
    return true
  }

  return evaluateConditions(scoped, values)
}
