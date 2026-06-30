import { asArray, asBoolean, asNumber, asRecord, asString, type MetadataRecord } from './metadata-common'

export type AdminHealthStatus = 'healthy' | 'warning' | 'unavailable' | 'unknown'
export type AdminDiagnosticStatus = 'loaded' | 'warning' | 'unavailable' | 'unknown'

export interface AdminPlatformInfo {
  heos_version?: string | null
  backend_version?: string | null
  frontend_version?: string | null
  environment?: string | null
  build_number?: string | null
  runtime_status?: string | null
  feature_counts?: MetadataRecord
}

export interface AdminOrganizationInfo {
  public_id?: string | null
  name?: string | null
  description?: string | null
  logo?: string | null
  brand?: string | null
  timezone?: string | null
  locale?: string | null
  currency?: string | null
  contact_info?: string | null
  created_at?: string | null
  status?: string | null
  slug?: string | null
}

export interface AdminWorkspaceInfo {
  public_id?: string | null
  name?: string | null
  slug?: string | null
  status?: string | null
  is_default?: boolean
  theme_source?: string | null
  application_count?: number
  navigation_count?: number
  personalization_summary?: MetadataRecord
}

export interface AdminUserProfile {
  public_id?: string | null
  display_name?: string | null
  email?: string | null
  status?: string | null
  roles?: string[]
  permissions?: string[]
  membership?: MetadataRecord
  avatar_url?: string | null
  session_expires_at?: string | null
}

export interface AdminRoleInfo {
  role_key: string
  label: string
  description?: string | null
  permission_count?: number
  member_count?: number | null
}

export interface AdminPermissionInfo {
  permission: string
  category: string
  label?: string | null
}

export interface AdminApplicationInfo {
  public_id: string
  name: string
  key?: string | null
  status?: string | null
  module_count?: number
  navigation_count?: number
  metadata?: MetadataRecord
}

export interface AdminApplicationRegistry {
  applications: AdminApplicationInfo[]
  totals: MetadataRecord
}

export interface AdminPlatformHealth {
  status: AdminHealthStatus
  summary?: string | null
  diagnostics?: MetadataRecord
  recommendations?: string[]
  source: 'backend' | 'runtime' | 'local'
}

export interface AdminRuntimeDiagnostic {
  key: string
  label: string
  status: AdminDiagnosticStatus
  detail?: string | null
}

export interface AdminApiDiagnostic {
  base_url: string
  authenticated: boolean
  tenant_headers: MetadataRecord
  latency_ms?: number | null
  connected_endpoints: string[]
}

export interface AdminFeatureFlag {
  key: string
  enabled: boolean
  description?: string | null
}

export interface AdminBindingContext {
  mode?: 'platform_overview' | 'runtime_summary' | 'organization_summary' | 'permission_browser' | 'role_browser'
  compact?: boolean
}

export function normalizeAdminPlatformInfo(raw: unknown, fallback: Partial<AdminPlatformInfo> = {}): AdminPlatformInfo {
  const data = asRecord(raw)
  const metadata = asRecord(data.runtime_metadata ?? data.metadata)
  return {
    heos_version: asString(data.heos_version ?? data.heosVersion ?? metadata.heos_version ?? metadata.platform_version, fallback.heos_version ?? 'HEOS Platform v1.0'),
    backend_version: asString(data.backend_version ?? data.backendVersion ?? metadata.backend_version ?? metadata.api_version, fallback.backend_version ?? undefined),
    frontend_version: asString(data.frontend_version ?? data.frontendVersion, fallback.frontend_version ?? undefined),
    environment: asString(data.environment ?? data.env, fallback.environment ?? undefined),
    build_number: asString(data.build_number ?? data.buildNumber ?? metadata.build_number, fallback.build_number ?? undefined),
    runtime_status: asString(data.runtime_status ?? data.runtimeStatus ?? data.status, fallback.runtime_status ?? undefined),
    feature_counts: asRecord(data.feature_counts ?? data.featureCounts ?? fallback.feature_counts),
  }
}

export function normalizeAdminOrganizationInfo(raw: unknown): AdminOrganizationInfo {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata ?? data.settings_metadata)
  return {
    public_id: asString(data.public_id ?? data.publicId) || null,
    name: asString(data.name ?? data.organization_name) || null,
    description: asString(data.description ?? metadata.description) || null,
    logo: asString(data.logo ?? metadata.logo ?? metadata.logo_url) || null,
    brand: asString(data.brand ?? metadata.brand ?? metadata.brand_name) || null,
    timezone: asString(data.timezone ?? metadata.timezone) || null,
    locale: asString(data.locale ?? metadata.locale) || null,
    currency: asString(data.currency ?? metadata.currency) || null,
    contact_info: asString(data.contact_info ?? data.contactInfo ?? metadata.contact_info) || null,
    created_at: typeof (data.created_at ?? data.createdAt) === 'string' ? ((data.created_at ?? data.createdAt) as string) : null,
    status: asString(data.status) || null,
    slug: asString(data.slug) || null,
  }
}

export function normalizeAdminWorkspaceInfo(raw: unknown, extras: Partial<AdminWorkspaceInfo> = {}): AdminWorkspaceInfo {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata ?? data.settings_metadata)
  return {
    public_id: asString(data.public_id ?? data.publicId) || null,
    name: asString(data.name) || null,
    slug: asString(data.slug) || null,
    status: asString(data.status) || null,
    is_default: asBoolean(data.is_default ?? data.isDefault, false),
    theme_source: extras.theme_source ?? asString(metadata.theme_source) ?? null,
    application_count: extras.application_count,
    navigation_count: extras.navigation_count,
    personalization_summary: extras.personalization_summary ?? metadata,
  }
}

export function normalizeAdminUserProfile(raw: unknown, extras: Partial<AdminUserProfile> = {}): AdminUserProfile {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId) || null,
    display_name: asString(data.display_name ?? data.displayName) || null,
    email: asString(data.email) || null,
    status: asString(data.status) || null,
    roles: extras.roles ?? asArray(data.roles).map((role) => asString(role)).filter(Boolean),
    permissions: extras.permissions ?? asArray(data.permissions).map((permission) => asString(permission)).filter(Boolean),
    membership: extras.membership ?? asRecord(data.membership),
    avatar_url: asString(data.avatar_url ?? data.avatarUrl) || null,
    session_expires_at: typeof (data.session_expires_at ?? extras.session_expires_at) === 'string'
      ? ((data.session_expires_at ?? extras.session_expires_at) as string)
      : null,
  }
}

export function normalizeAdminRoleInfo(raw: unknown): AdminRoleInfo {
  const data = asRecord(raw)
  const key = asString(data.role_key ?? data.roleKey ?? data.key ?? data.name, 'role')
  return {
    role_key: key,
    label: asString(data.label ?? data.name ?? key, key),
    description: asString(data.description ?? data.summary) || null,
    permission_count: asNumber(data.permission_count ?? data.permissionCount, 0),
    member_count: typeof (data.member_count ?? data.memberCount) === 'number' ? ((data.member_count ?? data.memberCount) as number) : null,
  }
}

export function normalizeAdminPermissionInfo(permission: string): AdminPermissionInfo {
  const [category] = permission.split('.')
  return {
    permission,
    category: category || 'general',
    label: permission.replace(/\./g, ' · '),
  }
}

export function normalizeAdminApplicationInfo(raw: unknown): AdminApplicationInfo {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  return {
    public_id: asString(data.public_id ?? data.publicId ?? data.key ?? data.name),
    name: asString(data.name ?? data.label, 'Application'),
    key: asString(data.key ?? data.application_key ?? metadata.module_key) || null,
    status: asString(data.status) || null,
    module_count: asNumber(data.module_count ?? metadata.module_count, 0) || undefined,
    navigation_count: asNumber(data.navigation_count ?? metadata.navigation_count, 0) || undefined,
    metadata,
  }
}

export function normalizeAdminPlatformHealth(raw: unknown, source: AdminPlatformHealth['source'] = 'backend'): AdminPlatformHealth {
  const data = asRecord(raw)
  const health = asString(data.health ?? data.status, 'unknown').toLowerCase()
  const status: AdminHealthStatus =
    health === 'healthy' || health === 'ok' ? 'healthy' : health === 'warning' || health === 'degraded' ? 'warning' : health === 'unavailable' || health === 'error' ? 'unavailable' : 'unknown'

  return {
    status,
    summary: asString(data.summary ?? data.message) || null,
    diagnostics: asRecord(data.diagnostics ?? data.integrity ?? data.dependency_summary),
    recommendations: asArray(data.recommendations).map((item) => asString(item)).filter(Boolean),
    source,
  }
}

export function normalizeAdminBindingContext(raw: MetadataRecord | undefined): AdminBindingContext {
  const config = asRecord(raw)
  const mode = asString(config.mode)
  const allowed = ['platform_overview', 'runtime_summary', 'organization_summary', 'permission_browser', 'role_browser']
  return {
    mode: allowed.includes(mode) ? (mode as AdminBindingContext['mode']) : 'platform_overview',
    compact: config.compact === true,
  }
}

export function normalizeAdminFeatureFlags(raw: unknown): AdminFeatureFlag[] {
  const data = asRecord(raw)
  return Object.entries(data).map(([key, value]) => ({
    key,
    enabled: typeof value === 'boolean' ? value : value === 'true' || value === 1,
    description: typeof value === 'object' && value !== null ? asString(asRecord(value).description) || null : null,
  }))
}
