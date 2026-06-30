import type { ApiRecord } from './api'
import type {
  ApplicationSummary,
  AuthUser,
  MembershipSummary,
  OrganizationSummary,
  WorkspaceSummary,
} from './auth'

export interface PersonalizationRuntimeResponse {
  preferences: ApiRecord[]
  favorites: ApiRecord[]
  recent_items: ApiRecord[]
  shortcuts: ApiRecord[]
  quick_actions: ApiRecord[]
  onboarding_state: ApiRecord
  theme_override: ApiRecord
  navigation_overrides: ApiRecord
  dashboard_overrides: ApiRecord
  table_overrides: ApiRecord
  notification_preferences_reference: ApiRecord
  warnings: string[]
  source: string
  runtime_context: {
    organization_public_id: string | null
    workspace_public_id: string | null
    membership_public_id: string | null
    status: string
    missing_tables: string[]
  }
}

export interface WorkspaceRuntimeResponse {
  organization: OrganizationSummary
  workspace: WorkspaceSummary
  membership: MembershipSummary
  active_applications: ApiRecord[]
  active_application: ApiRecord | null
  runtime_version: number
  settings_version: number
  runtime_metadata: ApiRecord
  capabilities: ApiRecord
  navigation: ApiRecord | ApiRecord[]
  feature_flags: ApiRecord
  module_diagnostics: ApiRecord
  settings_metadata: ApiRecord
}

export interface TenantContextResponse {
  user: AuthUser
  organization: OrganizationSummary
  membership: MembershipSummary
  workspace: WorkspaceSummary
  permissions: string[]
  runtime_summary: {
    active_application_count: number
    runtime_version: number
    settings_version: number
  }
}

export interface ThemeRuntimeResponse {
  definition: ApiRecord
  version: ApiRecord
  brand_profile: ApiRecord
  theme: ApiRecord
  runtime_context: ApiRecord
  permissions: ApiRecord
  warnings: string[]
  source: string
}

export interface NavigationMenuResponse {
  menu_key: string
  label: string
  groups: NavigationGroupResponse[]
  metadata: ApiRecord
}

export interface NavigationGroupResponse {
  group_key: string
  label: string
  sort_order?: number
  items: NavigationItemResponse[]
  metadata?: ApiRecord
}

export interface NavigationItemResponse {
  item_key: string
  label: string
  item_type?: string
  route?: ApiRecord | string
  badge?: string | null
  sort_order?: number
  required_permission?: string | null
  metadata?: ApiRecord
}

export interface NotificationSummaryResponse {
  public_id: string
  title: string
  read_at: string | null
}

export interface HydratedRuntimeBundle {
  tenantContext: TenantContextResponse | null
  workspaceRuntime: WorkspaceRuntimeResponse | null
  themeRuntime: ThemeRuntimeResponse | null
  personalizationRuntime: PersonalizationRuntimeResponse | null
  navigationMenus: NavigationMenuResponse[]
  permissions: string[]
  roles: string[]
  user: AuthUser | null
  organization: OrganizationSummary | null
  workspace: WorkspaceSummary | null
  membership: MembershipSummary | null
  application: ApplicationSummary | null
  unreadNotificationCount: number
  warnings: string[]
  source: string
}
