import type { UiComponent } from '@/api/types/ui'
import { normalizeAdminBindingContext } from '@/api/types/admin'
import { PlatformOverviewPanel } from '@/features/admin/components/PlatformOverviewPanel'
import { RuntimeDiagnosticsPanel } from '@/features/admin/components/RuntimeDiagnosticsPanel'
import { OrganizationSettingsPanel } from '@/features/admin/components/OrganizationSettingsPanel'
import { PermissionBrowserPanel } from '@/features/admin/components/PermissionBrowserPanel'
import { RoleBrowserPanel } from '@/features/admin/components/RoleBrowserPanel'
import { useAdminConsole } from '@/features/admin/hooks/useAdminConsole'

interface AdministrationBindingRendererProps {
  component: UiComponent
}

export function AdministrationBindingRenderer({ component }: AdministrationBindingRendererProps) {
  const binding = normalizeAdminBindingContext(component.binding_config)
  const admin = useAdminConsole()

  if (binding.mode === 'runtime_summary') {
    return (
      <div data-testid="administration-binding-renderer">
        <RuntimeDiagnosticsPanel diagnostics={admin.runtimeDiagnostics} />
      </div>
    )
  }

  if (binding.mode === 'organization_summary') {
    return (
      <div data-testid="administration-binding-renderer">
        <OrganizationSettingsPanel organization={admin.organization} />
      </div>
    )
  }

  if (binding.mode === 'permission_browser') {
    return (
      <div data-testid="administration-binding-renderer">
        <PermissionBrowserPanel permissions={admin.permissions} />
      </div>
    )
  }

  if (binding.mode === 'role_browser') {
    return (
      <div data-testid="administration-binding-renderer">
        <RoleBrowserPanel roles={admin.roles} />
      </div>
    )
  }

  return (
    <div data-testid="administration-binding-renderer">
      <PlatformOverviewPanel info={admin.platformOverview} />
    </div>
  )
}
