import type { AdminPlatformHealth } from '@/api/types/admin'
import { getHealthLabel } from '../core/admin-health'
import { AdminSection } from './AdminSection'
import { AdminStatusBadge } from './AdminStatusBadge'

export function PlatformHealthPanel({ health }: { health: AdminPlatformHealth }) {
  return (
    <AdminSection title="Platform Health" description="Derived from runtime hydration and workspace runtime health when available.">
      <div className="flex items-center gap-3">
        <AdminStatusBadge status={health.status} />
        <span className="text-sm text-foreground">{getHealthLabel(health.status)}</span>
        <span className="text-xs text-muted-foreground">Source: {health.source}</span>
      </div>
      {health.summary ? <p className="mt-3 text-sm text-muted-foreground">{health.summary}</p> : null}
      {health.recommendations && health.recommendations.length > 0 ? (
        <ul className="mt-3 list-disc space-y-1 pl-5 text-xs text-muted-foreground">
          {health.recommendations.map((item) => (
            <li key={item}>{item}</li>
          ))}
        </ul>
      ) : null}
    </AdminSection>
  )
}
