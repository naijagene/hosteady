import { Link, useRouterState } from '@tanstack/react-router'
import { filterAdminNavItems } from '../core/admin-permissions-guard'

interface AdminNavProps {
  permissions?: string[]
}

export function AdminNav({ permissions = [] }: AdminNavProps) {
  const pathname = useRouterState({ select: (state) => state.location.pathname })
  const items = filterAdminNavItems(permissions)

  return (
    <nav aria-label="Administration sections" className="space-y-1" data-testid="admin-nav">
      {items.map((item) => {
        const active = pathname === item.route || (item.route !== '/admin' && pathname.startsWith(item.route))
        return (
          <Link
            key={item.key}
            to={item.route}
            className={`block rounded-md px-3 py-2 text-sm ${active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'}`}
          >
            {item.label}
          </Link>
        )
      })}
    </nav>
  )
}
