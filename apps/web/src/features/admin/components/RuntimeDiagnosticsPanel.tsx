import type { AdminRuntimeDiagnostic } from '@/api/types/admin'
import { AdminSection } from './AdminSection'
import { AdminStatusBadge } from './AdminStatusBadge'

export function RuntimeDiagnosticsPanel({ diagnostics }: { diagnostics: AdminRuntimeDiagnostic[] }) {
  return (
    <AdminSection title="Runtime Diagnostics">
      <ul className="space-y-2" data-testid="runtime-diagnostics">
        {diagnostics.map((item) => (
          <li key={item.key} className="flex items-center justify-between gap-3 rounded-md border border-border px-3 py-2 text-sm">
            <div>
              <div className="font-medium text-foreground">{item.label}</div>
              <div className="text-xs text-muted-foreground">{item.detail}</div>
            </div>
            <AdminStatusBadge status={item.status} />
          </li>
        ))}
      </ul>
    </AdminSection>
  )
}
