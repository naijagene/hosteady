import type { ActivityEntityType } from '@/api/types/activity'
import { resolveActivityIcon } from '../core/activity-icons'

interface ActivityEntityBadgeProps {
  type?: ActivityEntityType | string | null
  label?: string | null
}

export function ActivityEntityBadge({ type, label }: ActivityEntityBadgeProps) {
  const icon = resolveActivityIcon(type)
  return (
    <span className="inline-flex items-center gap-1 rounded-md border border-border bg-muted px-2 py-0.5 text-[10px] uppercase text-muted-foreground">
      <span aria-hidden>{icon.slice(0, 2)}</span>
      {label ?? type ?? 'Resource'}
    </span>
  )
}
