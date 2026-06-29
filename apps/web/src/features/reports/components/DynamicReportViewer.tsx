import { useCallback, useMemo, useState } from 'react'
import { runReport } from '@/api/endpoints/reports'
import type { ReportBindingContext, ReportRenderPayload, ReportRunResult } from '@/api/types/reports'
import { useReportActions } from '../hooks/useReportActions'
import { useReportExport } from '../hooks/useReportExport'
import { useReportParameters } from '../hooks/useReportParameters'
import { useReportRender } from '../hooks/useReportRender'
import { toReportQueryError } from '../core/report-errors'
import { ReportEmptyState } from './ReportEmptyState'
import { ReportHeader } from './ReportHeader'
import { ReportParameterPanel } from './ReportParameterPanel'
import { ReportRunStatus } from './ReportRunStatus'
import { ReportSectionRenderer } from './ReportSectionRenderer'
import { ReportToolbar } from './ReportToolbar'

interface DynamicReportViewerProps {
  payload: ReportRenderPayload
  binding?: ReportBindingContext
  onRefresh?: () => void
}

export function DynamicReportViewer({ payload, binding, onRefresh }: DynamicReportViewerProps) {
  const { title, description, parameters, sections, actions } = useReportRender({
    payload,
    binding,
  })
  const parameterState = useReportParameters(
    binding?.parameters?.length ? binding.parameters : parameters,
  )
  const exportState = useReportExport({
    moduleKey: binding?.moduleKey ?? payload.report.module_key,
    reportKey: binding?.reportKey ?? payload.report.report_key,
    enabled: binding?.export_enabled !== false,
  })
  const [runResult, setRunResult] = useState<ReportRunResult | null>(null)
  const [runError, setRunError] = useState<string | null>(null)
  const [isRunning, setIsRunning] = useState(false)

  const handleRun = useCallback(async () => {
    const moduleKey = binding?.moduleKey ?? payload.report.module_key
    const reportKey = binding?.reportKey ?? payload.report.report_key

    if (!moduleKey || !reportKey || binding?.run_enabled === false) {
      setRunError('Report run is not enabled.')
      return
    }

    setIsRunning(true)
    setRunError(null)

    try {
      const result = await runReport(moduleKey, reportKey, {
        parameters: parameterState.serializedParameters,
      })
      setRunResult(result)
    } catch (error) {
      setRunResult(null)
      setRunError(toReportQueryError(error).message)
    } finally {
      setIsRunning(false)
    }
  }, [binding, payload.report.module_key, payload.report.report_key, parameterState.serializedParameters])

  const exportMessage = useMemo(() => {
    if (exportState.error) {
      return exportState.error
    }

    if (exportState.result?.file_reference) {
      const fileName =
        typeof exportState.result.file_reference.file_name === 'string'
          ? exportState.result.file_reference.file_name
          : 'Export ready'
      return `${fileName} (${exportState.result.export_format ?? 'file'})`
    }

    if (exportState.result) {
      return `Export placeholder · ${exportState.result.status ?? 'completed'}`
    }

    return null
  }, [exportState.error, exportState.result])

  const actionState = useReportActions({
    onRefresh,
    onRun: handleRun,
    onExport: () => exportState.exportReportFile({ export_format: 'pdf', parameters: parameterState.serializedParameters }),
  })

  return (
    <section
      className="overflow-hidden rounded-lg border border-border bg-card"
      data-testid="dynamic-report-viewer"
      aria-label={title}
    >
      <div className="space-y-3 px-4 py-3">
        <ReportHeader title={title} description={description} />
      </div>
      <ReportParameterPanel
        parameters={binding?.parameters?.length ? binding.parameters : parameters}
        values={parameterState.values}
        warnings={parameterState.warnings}
        onChange={parameterState.setParameterValue}
        onApply={parameterState.applyParameters}
        onReset={parameterState.resetParameters}
      />
      <ReportToolbar
        actions={actions}
        onAction={actionState.handleAction}
        message={actionState.message}
        exportEnabled={binding?.export_enabled !== false}
        onExport={(format) =>
          exportState.exportReportFile({
            export_format: format,
            parameters: parameterState.serializedParameters,
          })
        }
        isExporting={exportState.isExporting}
        exportMessage={exportMessage}
      />
      <div className="space-y-4 px-4 py-4">
        <ReportRunStatus isRunning={isRunning} result={runResult} error={runError} />
        {sections.length === 0 ? (
          <ReportEmptyState message={binding?.empty_state_message} />
        ) : (
          sections.map((section) => (
            <ReportSectionRenderer key={section.section_key} section={section} />
          ))
        )}
      </div>
    </section>
  )
}
