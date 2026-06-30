import type { ActivityActor } from '@/api/types/activity'

interface ActivityActorBadgeProps {
  actor?: ActivityActor | null
}

export function ActivityActorBadge({ actor }: ActivityActorBadgeProps) {
  const label = actor?.display_name ?? actor?.email ?? actor?.type ?? 'System'
  return <span className="text-xs text-muted-foreground">{label}</span>
}
