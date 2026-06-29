interface DashboardMetricCardProps {
  title: string
  value: string
  prefix?: string
  suffix?: string
  trend?: string | null
  comparison?: string | null
  status?: string | null
  icon?: string | null
  empty?: boolean
  loading?: boolean
}

export function DashboardMetricCard({
  title,
  value,
  prefix,
  suffix,
  trend,
  comparison,
  status,
  icon,
  empty = false,
  loading = false,
}: DashboardMetricCardProps) {
  return (
    <div className="space-y-2" data-testid="dashboard-metric-card" aria-label={`${title} metric`}>
      <div className="flex items-center justify-between gap-2">
        <h4 className="text-sm font-medium text-foreground">{title}</h4>
        {icon ? <span className="text-xs text-muted-foreground">{icon}</span> : null}
      </div>
      {loading ? (
        <p className="text-xs text-muted-foreground">Loading metric…</p>
      ) : empty ? (
        <p className="text-xs text-muted-foreground">No metric data</p>
      ) : (
        <p className="text-2xl font-semibold tabular-nums text-foreground">
          {prefix ? <span className="text-base text-muted-foreground">{prefix}</span> : null}
          {value}
          {suffix ? <span className="text-base text-muted-foreground">{suffix}</span> : null}
        </p>
      )}
      {trend ? <p className="text-xs text-muted-foreground">Trend: {trend}</p> : null}
      {comparison ? <p className="text-xs text-muted-foreground">{comparison}</p> : null}
      {status ? (
        <span className="inline-flex rounded-full bg-muted px-2 py-0.5 text-xs text-foreground">
          {status}
        </span>
      ) : null}
    </div>
  )
}
