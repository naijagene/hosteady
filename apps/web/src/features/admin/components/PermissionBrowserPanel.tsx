import { copyPermissionToClipboard } from '../core/admin-permissions'
import { usePermissionBrowser } from '../hooks/usePermissionBrowser'
import { AdminSection } from './AdminSection'

interface PermissionBrowserPanelProps {
  permissions: string[]
}

export function PermissionBrowserPanel({ permissions }: PermissionBrowserPanelProps) {
  const browser = usePermissionBrowser(permissions)

  return (
    <AdminSection title="Permission Browser" description="Search and copy hydrated runtime permissions.">
      <input
        type="search"
        aria-label="Search permissions"
        value={browser.search}
        onChange={(event) => browser.setSearch(event.target.value)}
        placeholder="Search permissions…"
        className="mb-4 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
        data-testid="permission-search"
      />
      <div className="space-y-4" data-testid="permission-browser">
        {Object.entries(browser.groups).map(([category, items]) => (
          <section key={category} aria-label={`${category} permissions`}>
            <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">{category}</h3>
            <ul className="space-y-2">
              {items.map((item) => (
                <li key={item.permission} className="flex items-center justify-between gap-3 rounded-md border border-border px-3 py-2 text-sm">
                  <span>{item.permission}</span>
                  <button
                    type="button"
                    className="text-xs text-primary"
                    aria-label={`Copy permission ${item.permission}`}
                    onClick={() => void copyPermissionToClipboard(item.permission)}
                  >
                    Copy
                  </button>
                </li>
              ))}
            </ul>
          </section>
        ))}
      </div>
    </AdminSection>
  )
}
