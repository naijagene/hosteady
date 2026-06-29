interface ReportErrorStateProps {
  message?: string
}

export function ReportErrorState({ message = 'Unable to load report.' }: ReportErrorStateProps) {
  return (
    <div
      className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
      data-testid="report-error-state"
      role="alert"
    >
      {message}
    </div>
  )
}
