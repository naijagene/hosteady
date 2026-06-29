import { useCallback, useState } from 'react'
import { exportReport } from '@/api/endpoints/reports'
import type { ReportExportPayload, ReportExportResult } from '@/api/types/reports'
import { toReportQueryError } from '../core/report-errors'

export function useReportExport(options: {
  moduleKey: string
  reportKey: string
  enabled?: boolean
}) {
  const [isExporting, setIsExporting] = useState(false)
  const [result, setResult] = useState<ReportExportResult | null>(null)
  const [error, setError] = useState<string | null>(null)

  const exportReportFile = useCallback(
    async (payload: ReportExportPayload) => {
      if (options.enabled === false) {
        setError('Export is not enabled for this report.')
        return null
      }

      setIsExporting(true)
      setError(null)

      try {
        const exportResult = await exportReport(options.moduleKey, options.reportKey, payload)
        setResult(exportResult)
        return exportResult
      } catch (caught) {
        const normalized = toReportQueryError(caught)
        setError(normalized.message)
        setResult(null)
        return null
      } finally {
        setIsExporting(false)
      }
    },
    [options.enabled, options.moduleKey, options.reportKey],
  )

  return {
    isExporting,
    result,
    error,
    exportReportFile,
    clearExportState: () => {
      setResult(null)
      setError(null)
    },
  }
}
