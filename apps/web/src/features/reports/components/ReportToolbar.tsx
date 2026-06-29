import type { ReportAction } from '@/api/types/reports'
import { ReportExportMenu } from './ReportExportMenu'

interface ReportToolbarProps {
  actions: ReportAction[]
  onAction: (action: ReportAction) => void
  message?: string | null
  exportEnabled?: boolean
  onExport?: (format: 'pdf' | 'xlsx' | 'csv' | 'json') => void
  isExporting?: boolean
  exportMessage?: string | null
}

export function ReportToolbar({
  actions,
  onAction,
  message,
  exportEnabled = true,
  onExport,
  isExporting = false,
  exportMessage,
}: ReportToolbarProps) {
  const toolbarActions = actions.filter((action) => action.action_type.toLowerCase() !== 'export')

  return (
    <div
      className="space-y-2 border-b border-border px-4 py-3"
      data-testid="report-toolbar"
      aria-label="Report toolbar"
    >
      <div className="flex flex-wrap items-center justify-end gap-2">
        {toolbarActions.map((action) => (
          <button
            key={action.action_key}
            type="button"
            className="rounded-md border border-border px-3 py-1 text-xs text-foreground hover:bg-muted"
            aria-label={action.label}
            onClick={() => onAction(action)}
          >
            {action.label}
          </button>
        ))}
        {exportEnabled ? (
          <ReportExportMenu onExport={onExport} isExporting={isExporting} message={exportMessage} />
        ) : null}
      </div>
      {message ? (
        <p className="text-xs text-muted-foreground" role="status">
          {message}
        </p>
      ) : null}
    </div>
  )
}
