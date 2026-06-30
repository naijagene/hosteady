import type { AdminApiDiagnostic } from '@/api/types/admin'
import { AdminDefinitionList } from './AdminDefinitionList'
import { AdminSection } from './AdminSection'

export function ApiDiagnosticsPanel({ diagnostics }: { diagnostics: AdminApiDiagnostic }) {
  return (
    <div className="space-y-4">
      <AdminSection title="API Diagnostics">
        <AdminDefinitionList
          items={[
            { label: 'API Base URL', value: diagnostics.base_url },
            { label: 'Authenticated', value: diagnostics.authenticated ? 'Yes' : 'No' },
            { label: 'Latency', value: diagnostics.latency_ms ? `${diagnostics.latency_ms} ms` : 'Latency measurement unavailable' },
          ]}
        />
      </AdminSection>
      <AdminSection title="Tenant Headers">
        <pre className="overflow-auto rounded-md bg-muted p-3 text-xs">{JSON.stringify(diagnostics.tenant_headers, null, 2)}</pre>
      </AdminSection>
      <AdminSection title="Connected Endpoints">
        <ul className="space-y-1 text-xs text-muted-foreground">
          {diagnostics.connected_endpoints.map((endpoint) => (
            <li key={endpoint}>{endpoint}</li>
          ))}
        </ul>
      </AdminSection>
    </div>
  )
}
