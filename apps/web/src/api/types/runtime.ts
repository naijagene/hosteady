import type { ApiRecord } from './api'

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
  organization: ApiRecord
  workspace: ApiRecord
  membership: ApiRecord
  active_applications: ApiRecord[]
  active_application: ApiRecord | null
  runtime_version: number
  settings_version: number
  runtime_metadata: ApiRecord
  capabilities: ApiRecord
  navigation: ApiRecord
  feature_flags: ApiRecord
  module_diagnostics: ApiRecord
  settings_metadata: ApiRecord
}

export interface TenantContextResponse {
  organization: ApiRecord
  workspace: ApiRecord | null
  membership: ApiRecord
  user: ApiRecord
}
