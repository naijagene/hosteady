interface ReportMetricCardProps {
  title: string
  value: string
  prefix?: string
  suffix?: string
  trend?: string | null
  comparison?: string | null
  status?: string | null
  empty?: boolean
}

export function ReportMetricCard({
  title,
  value,
  prefix,
  suffix,
  trend,
  comparison,
  status,
  empty = false,
}: ReportMetricCardProps) {
  return (
    <div
      className="rounded-md border border-border bg-muted/10 p-3"
      data-testid="report-metric-card"
      aria-label={`${title} metric`}
    >
      <h4 className="text-xs font-medium text-muted-foreground">{title}</h4>
      {empty ? (
        <p className="mt-2 text-xs text-muted-foreground">No metric data</p>
      ) : (
        <p className="mt-2 text-xl font-semibold tabular-nums text-foreground">
          {prefix ? <span className="text-sm text-muted-foreground">{prefix}</span> : null}
          {value}
          {suffix ? <span className="text-sm text-muted-foreground">{suffix}</span> : null}
        </p>
      )}
      {trend ? <p className="mt-1 text-xs text-muted-foreground">Trend: {trend}</p> : null}
      {comparison ? <p className="mt-1 text-xs text-muted-foreground">{comparison}</p> : null}
      {status ? (
        <span className="mt-2 inline-flex rounded-full bg-muted px-2 py-0.5 text-xs text-foreground">
          {status}
        </span>
      ) : null}
    </div>
  )
}
