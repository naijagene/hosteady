import type { AdminApplicationRegistry } from '@/api/types/admin'
import { AdminSection } from './AdminSection'

export function ApplicationRegistryPanel({ registry }: { registry: AdminApplicationRegistry }) {
  return (
    <div className="space-y-4">
      <AdminSection title="Application Registry">
        <dl className="grid grid-cols-2 gap-3 text-xs md:grid-cols-4">
          {Object.entries(registry.totals).map(([key, value]) => (
            <div key={key}>
              <dt className="text-muted-foreground">{key}</dt>
              <dd className="text-lg font-semibold text-foreground">{String(value)}</dd>
            </div>
          ))}
        </dl>
      </AdminSection>
      <AdminSection title="Installed Applications">
        <ul className="space-y-2">
          {registry.applications.map((app) => (
            <li key={app.public_id} className="rounded-md border border-border px-3 py-2 text-sm">
              <div className="font-medium text-foreground">{app.name}</div>
              <div className="text-xs text-muted-foreground">{app.key ?? app.public_id}</div>
            </li>
          ))}
        </ul>
      </AdminSection>
    </div>
  )
}
