import type { AdminFeatureFlag } from '@/api/types/admin'
import { AdminSection } from './AdminSection'
import { AdminStatusBadge } from './AdminStatusBadge'

export function FeatureFlagsPanel({ flags }: { flags: AdminFeatureFlag[] }) {
  return (
    <AdminSection title="Feature Flags">
      <ul className="space-y-2" data-testid="feature-flags">
        {flags.map((flag) => (
          <li key={flag.key} className="flex items-center justify-between gap-3 rounded-md border border-border px-3 py-2 text-sm">
            <div>
              <div className="font-medium text-foreground">{flag.key}</div>
              {flag.description ? <div className="text-xs text-muted-foreground">{flag.description}</div> : null}
            </div>
            <AdminStatusBadge status={flag.enabled ? 'loaded' : 'unavailable'} />
          </li>
        ))}
      </ul>
    </AdminSection>
  )
}
