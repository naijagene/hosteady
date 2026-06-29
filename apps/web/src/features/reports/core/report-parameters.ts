import type { ReportParameter } from '@/api/types/reports'

export function getParameterValueKey(parameter: ReportParameter): string {
  return parameter.parameter_key
}

export function createInitialParameterValues(parameters: ReportParameter[]): Record<string, unknown> {
  return parameters.reduce<Record<string, unknown>>((accumulator, parameter) => {
    accumulator[getParameterValueKey(parameter)] = parameter.default_value ?? ''
    return accumulator
  }, {})
}

export function isSupportedParameterType(type: string): boolean {
  return ['text', 'number', 'date', 'date_range', 'select', 'multiselect', 'boolean', 'hidden'].includes(
    type.toLowerCase(),
  )
}

export function validateRequiredParameters(
  parameters: ReportParameter[],
  values: Record<string, unknown>,
): string[] {
  return parameters
    .filter((parameter) => parameter.required)
    .filter((parameter) => {
      const value = values[getParameterValueKey(parameter)]
      return value === undefined || value === null || value === ''
    })
    .map((parameter) => `${parameter.label} is required`)
}

export function serializeReportParameters(
  parameters: ReportParameter[],
  values: Record<string, unknown>,
): Record<string, unknown> {
  return parameters.reduce<Record<string, unknown>>((accumulator, parameter) => {
    const key = getParameterValueKey(parameter)
    if (parameter.parameter_type.toLowerCase() !== 'hidden') {
      accumulator[key] = values[key]
    } else {
      accumulator[key] = parameter.default_value
    }
    return accumulator
  }, {})
}
