import type { ReportRunResult } from '@/api/types/reports'

interface ReportRunStatusProps {
  isRunning?: boolean
  result?: ReportRunResult | null
  error?: string | null
}

export function ReportRunStatus({ isRunning = false, result, error }: ReportRunStatusProps) {
  if (isRunning) {
    return (
      <p className="text-xs text-muted-foreground" data-testid="report-run-status" role="status" aria-busy="true">
        Running report…
      </p>
    )
  }

  if (error) {
    return (
      <p className="text-xs text-destructive" data-testid="report-run-status" role="alert">
        {error}
      </p>
    )
  }

  if (!result) {
    return null
  }

  return (
    <p className="text-xs text-muted-foreground" data-testid="report-run-status" role="status">
      Report run {result.status ?? 'completed'}
      {result.duration_ms ? ` · ${result.duration_ms}ms` : ''}
    </p>
  )
}
