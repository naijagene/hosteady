import type { AdminWorkspaceInfo } from '@/api/types/admin'
import { getWorkspaceDisplayFields } from '../core/admin-workspace'
import { AdminDefinitionList } from './AdminDefinitionList'
import { AdminSection } from './AdminSection'

interface WorkspaceSettingsPanelProps {
  current: AdminWorkspaceInfo
  workspaces: AdminWorkspaceInfo[]
}

export function WorkspaceSettingsPanel({ current, workspaces }: WorkspaceSettingsPanelProps) {
  return (
    <div className="space-y-4">
      <AdminSection title="Current Workspace">
        <AdminDefinitionList items={getWorkspaceDisplayFields(current)} />
        {current.personalization_summary ? (
          <div className="mt-4 rounded-md border border-dashed border-border p-3 text-xs text-muted-foreground">
            Personalization: {JSON.stringify(current.personalization_summary)}
          </div>
        ) : null}
      </AdminSection>
      <AdminSection title="Workspace List">
        <ul className="space-y-2 text-sm">
          {workspaces.map((workspace) => (
            <li key={workspace.public_id ?? workspace.name} className="rounded-md border border-border px-3 py-2">
              <span className="font-medium text-foreground">{workspace.name}</span>
              <span className="ml-2 text-xs text-muted-foreground">{workspace.slug}</span>
              {workspace.is_default ? <span className="ml-2 text-xs text-primary">Default</span> : null}
            </li>
          ))}
        </ul>
      </AdminSection>
    </div>
  )
}
