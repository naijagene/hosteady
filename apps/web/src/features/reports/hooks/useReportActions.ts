import { useCallback, useState } from 'react'
import type { ReportAction } from '@/api/types/reports'
import { getReportActionPlaceholder } from '../core/report-actions'

export function useReportActions(options?: {
  onRefresh?: () => void
  onRun?: () => void
  onExport?: () => void
}) {
  const [message, setMessage] = useState<string | null>(null)

  const handleAction = useCallback(
    (action: ReportAction) => {
      const type = action.action_type.toLowerCase()

      switch (type) {
        case 'refresh':
          options?.onRefresh?.()
          setMessage(null)
          return
        case 'run':
          options?.onRun?.()
          setMessage(null)
          return
        case 'export':
          options?.onExport?.()
          return
        default:
          setMessage(getReportActionPlaceholder(action))
      }
    },
    [options],
  )

  return {
    message,
    handleAction,
    clearMessage: () => setMessage(null),
  }
}
