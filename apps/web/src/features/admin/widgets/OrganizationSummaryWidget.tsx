import { useAdminConsole } from '../hooks/useAdminConsole'
import { getOrganizationDisplayFields } from '../core/admin-organization'
import { AdminDefinitionList } from '../components/AdminDefinitionList'

export function OrganizationSummaryWidget() {
  const admin = useAdminConsole()
  return (
    <section className="rounded-lg border border-border bg-card p-4" data-testid="organization-summary-widget">
      <h2 className="text-sm font-semibold text-foreground">Organization Summary</h2>
      <div className="mt-3">
        <AdminDefinitionList items={getOrganizationDisplayFields(admin.organization).slice(0, 4)} />
      </div>
    </section>
  )
}
