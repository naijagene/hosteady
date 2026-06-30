import type { ActivitySeverity } from '@/api/types/activity'
import { getSeverityLabel, getSeverityTone } from '../core/activity-severity'

interface ActivitySeverityBadgeProps {
  severity?: ActivitySeverity
}

export function ActivitySeverityBadge({ severity }: ActivitySeverityBadgeProps) {
  const tone = getSeverityTone(severity)
  const classes =
    tone === 'critical'
      ? 'border-destructive/30 bg-destructive/10 text-destructive'
      : tone === 'warning'
        ? 'border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300'
        : 'border-border bg-muted text-muted-foreground'

  return (
    <span className={`inline-flex rounded-full border px-2 py-0.5 text-[10px] font-medium uppercase ${classes}`}>
      {getSeverityLabel(severity)}
    </span>
  )
}
