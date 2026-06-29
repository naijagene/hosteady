import { useCallback, useMemo, useState } from 'react'
import type { ReportParameter } from '@/api/types/reports'
import {
  createInitialParameterValues,
  getParameterValueKey,
  serializeReportParameters,
  validateRequiredParameters,
} from '../core/report-parameters'

export function useReportParameters(parameters: ReportParameter[] = []) {
  const [values, setValues] = useState<Record<string, unknown>>(() =>
    createInitialParameterValues(parameters),
  )
  const [applied, setApplied] = useState<Record<string, unknown>>(() =>
    createInitialParameterValues(parameters),
  )
  const [warnings, setWarnings] = useState<string[]>([])

  const setParameterValue = useCallback((parameterKey: string, value: unknown) => {
    setValues((current) => ({ ...current, [parameterKey]: value }))
  }, [])

  const applyParameters = useCallback(() => {
    const nextWarnings = validateRequiredParameters(parameters, values)
    setWarnings(nextWarnings)

    if (nextWarnings.length === 0) {
      setApplied(values)
    }
  }, [parameters, values])

  const resetParameters = useCallback(() => {
    const initial = createInitialParameterValues(parameters)
    setValues(initial)
    setApplied(initial)
    setWarnings([])
  }, [parameters])

  const serializedParameters = useMemo(
    () => serializeReportParameters(parameters, applied),
    [parameters, applied],
  )

  return {
    values,
    applied,
    warnings,
    setParameterValue,
    applyParameters,
    resetParameters,
    serializedParameters,
    getParameterValueKey,
  }
}
