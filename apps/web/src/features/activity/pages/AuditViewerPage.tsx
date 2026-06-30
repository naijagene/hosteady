import { useActivityFilters } from '../hooks/useActivityFilters'
import { useAuditLog } from '../hooks/useAuditLog'
import { ActivityToolbar } from '../components/ActivityToolbar'
import { AuditViewer } from '../components/AuditViewer'

export function AuditViewerPage() {
  const { query, setSearch } = useActivityFilters({ metadata: { source: 'web', binding: 'audit_viewer' } })
  const audit = useAuditLog(query)

  return (
    <div className="space-y-4" data-testid="audit-viewer-page">
      <div>
        <h1 className="text-2xl font-semibold text-foreground">Audit Viewer</h1>
        <p className="mt-1 text-sm text-muted-foreground">Review platform audit events and change history.</p>
      </div>
      <ActivityToolbar search={query.search ?? ''} onSearchChange={setSearch} onRefresh={audit.refresh} source="Platform audit" />
      <AuditViewer items={audit.items} isLoading={audit.isLoading} error={audit.error?.message ?? null} />
    </div>
  )
}
