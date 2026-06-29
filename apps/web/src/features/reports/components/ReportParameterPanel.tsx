import type { ReportParameter } from '@/api/types/reports'
import { getParameterValueKey, isSupportedParameterType } from '../core/report-parameters'

interface ReportParameterPanelProps {
  parameters: ReportParameter[]
  values: Record<string, unknown>
  warnings?: string[]
  onChange: (parameterKey: string, value: unknown) => void
  onApply: () => void
  onReset: () => void
}

function renderParameterInput(
  parameter: ReportParameter,
  value: unknown,
  onChange: (parameterKey: string, value: unknown) => void,
) {
  const key = getParameterValueKey(parameter)
  const type = parameter.parameter_type.toLowerCase()

  if (type === 'hidden') {
    return null
  }

  if (!isSupportedParameterType(type)) {
    return (
      <p className="text-xs text-muted-foreground">
        Unsupported parameter type: {parameter.parameter_type}
      </p>
    )
  }

  if (type === 'boolean') {
    return (
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={Boolean(value)}
          aria-label={parameter.label}
          onChange={(event) => onChange(key, event.target.checked)}
        />
        {parameter.label}
      </label>
    )
  }

  if (type === 'select' || type === 'multiselect') {
    return (
      <select
        className="w-full rounded-md border border-border bg-background px-2 py-1 text-sm"
        aria-label={parameter.label}
        multiple={type === 'multiselect'}
        value={
          type === 'multiselect'
            ? (Array.isArray(value) ? value.map(String) : [])
            : String(value ?? '')
        }
        onChange={(event) => {
          if (type === 'multiselect') {
            onChange(
              key,
              Array.from(event.target.selectedOptions).map((option) => option.value),
            )
            return
          }

          onChange(key, event.target.value)
        }}
      >
        <option value="">Select…</option>
        {(parameter.options ?? []).map((option, index) => {
          const optionValue = String(option.value ?? option.key ?? index)
          const optionLabel = String(option.label ?? option.name ?? optionValue)
          return (
            <option key={optionValue} value={optionValue}>
              {optionLabel}
            </option>
          )
        })}
      </select>
    )
  }

  const inputType =
    type === 'number' ? 'number' : type === 'date' || type === 'date_range' ? 'date' : 'text'

  return (
    <input
      className="w-full rounded-md border border-border bg-background px-2 py-1 text-sm"
      type={inputType}
      aria-label={parameter.label}
      value={String(value ?? '')}
      onChange={(event) => onChange(key, event.target.value)}
    />
  )
}

export function ReportParameterPanel({
  parameters,
  values,
  warnings = [],
  onChange,
  onApply,
  onReset,
}: ReportParameterPanelProps) {
  const visibleParameters = parameters.filter(
    (parameter) => parameter.parameter_type.toLowerCase() !== 'hidden',
  )

  if (parameters.length === 0) {
    return null
  }

  return (
    <section
      className="space-y-3 border-b border-border px-4 py-3"
      data-testid="report-parameter-panel"
      aria-label="Report parameters"
    >
      <h3 className="text-sm font-medium text-foreground">Parameters</h3>
      <div className="grid gap-3 md:grid-cols-2">
        {visibleParameters.map((parameter) => (
          <div key={getParameterValueKey(parameter)} className="space-y-1">
            {parameter.parameter_type.toLowerCase() !== 'boolean' ? (
              <label className="text-xs font-medium text-muted-foreground">
                {parameter.label}
                {parameter.required ? ' *' : ''}
              </label>
            ) : null}
            {renderParameterInput(parameter, values[getParameterValueKey(parameter)], onChange)}
          </div>
        ))}
      </div>
      {warnings.length > 0 ? (
        <div className="space-y-1" role="alert">
          {warnings.map((warning) => (
            <p key={warning} className="text-xs text-destructive">
              {warning}
            </p>
          ))}
        </div>
      ) : null}
      <div className="flex flex-wrap gap-2">
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
          aria-label="Apply report parameters"
          onClick={onApply}
        >
          Apply
        </button>
        <button
          type="button"
          className="rounded-md border border-border px-3 py-1 text-xs text-muted-foreground hover:bg-muted"
          aria-label="Reset report parameters"
          onClick={onReset}
        >
          Reset
        </button>
      </div>
    </section>
  )
}
