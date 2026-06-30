import type { AdminHealthStatus, AdminDiagnosticStatus } from '@/api/types/admin'

interface AdminStatusBadgeProps {
  status: AdminHealthStatus | AdminDiagnosticStatus | string
}

export function AdminStatusBadge({ status }: AdminStatusBadgeProps) {
  const normalized = status.toLowerCase()
  const classes =
    normalized === 'healthy' || normalized === 'loaded'
      ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
      : normalized === 'warning'
        ? 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300'
        : normalized === 'unavailable'
          ? 'border-destructive/30 bg-destructive/10 text-destructive'
          : 'border-border bg-muted text-muted-foreground'

  return (
    <span className={`inline-flex rounded-full border px-2 py-0.5 text-[10px] font-medium uppercase ${classes}`}>
      {status}
    </span>
  )
}
