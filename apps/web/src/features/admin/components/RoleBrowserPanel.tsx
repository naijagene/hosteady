import type { AdminRoleInfo } from '@/api/types/admin'
import { AdminSection } from './AdminSection'

export function RoleBrowserPanel({ roles }: { roles: AdminRoleInfo[] }) {
  return (
    <AdminSection title="Role Browser" description="Browse platform roles from hydrated runtime.">
      <div className="space-y-3" data-testid="role-browser">
        {roles.map((role) => (
          <article key={role.role_key} className="rounded-md border border-border p-3">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <h3 className="text-sm font-medium text-foreground">{role.label}</h3>
              <span className="text-xs text-muted-foreground">{role.permission_count ?? 0} permissions</span>
            </div>
            <p className="mt-1 text-xs text-muted-foreground">{role.description ?? 'No description'}</p>
            <p className="mt-1 text-xs text-muted-foreground">Members: {role.member_count ?? 'Unavailable'}</p>
          </article>
        ))}
      </div>
    </AdminSection>
  )
}
