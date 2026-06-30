import { asArray, asBoolean, asNumber, asRecord, asString, type MetadataRecord } from './metadata-common'

export type ActivitySeverity = 'info' | 'warning' | 'critical' | 'low' | 'medium' | 'high' | string
export type ActivitySource = 'backend' | 'runtime' | 'local' | 'workflow' | 'document' | 'entity'
export type ActivityEntityType =
  | 'document'
  | 'workflow'
  | 'task'
  | 'approval'
  | 'report'
  | 'dashboard'
  | 'form'
  | 'table'
  | 'notification'
  | 'record'
  | 'user'
  | 'workspace'
  | 'security'
  | 'system'
  | 'custom'
  | string

export interface ActivityActor {
  type?: string | null
  user_public_id?: string | null
  membership_public_id?: string | null
  display_name?: string | null
  email?: string | null
}

export interface ActivityTarget {
  type?: ActivityEntityType | string | null
  public_id?: string | null
  label?: string | null
  module_key?: string | null
  entity_key?: string | null
  route?: string | null
}

export interface ActivityChangeSet {
  field?: string | null
  before?: unknown
  after?: unknown
  change_type?: 'added' | 'removed' | 'updated' | string | null
  sensitive?: boolean
}

export interface ActivityAction {
  action_key?: string | null
  label?: string | null
  route?: string | null
  metadata?: MetadataRecord
}

export interface ActivityFilter {
  key: string
  value: string
}

export interface ActivityQueryPayload {
  page?: number
  per_page?: number
  search?: string
  filters?: ActivityFilter[]
  sorts?: Array<{ key: string; direction?: 'asc' | 'desc' }>
  entity_type?: ActivityEntityType | string
  entity_public_id?: string
  workspace_public_id?: string
  actor_user_public_id?: string
  actor_membership_public_id?: string
  action?: string
  category?: string
  severity?: ActivitySeverity
  occurred_from?: string
  occurred_to?: string
  cursor?: string
  metadata?: MetadataRecord
}

export interface ActivityQueryResult {
  items: ActivityEntry[]
  page: number
  per_page: number
  total: number
  has_more: boolean
  next_cursor?: string | null
  source: ActivitySource
}

export interface ActivityEntry {
  public_id: string
  occurred_at?: string | null
  action: string
  summary?: string | null
  severity?: ActivitySeverity
  category?: string | null
  actor?: ActivityActor | null
  entity?: ActivityTarget | null
  changes?: ActivityChangeSet[]
  metadata?: MetadataRecord
  context?: MetadataRecord
  module_key?: string | null
  workspace_public_id?: string | null
  organization_public_id?: string | null
  source: ActivitySource
  permission?: string | null
}

export interface AuditEntry extends ActivityEntry {
  event_version?: number
  retention_class?: string | null
  expires_at?: string | null
  ip_address?: string | null
  user_agent?: string | null
  request_id?: string | null
}

export interface HistoryEntry extends ActivityEntry {
  change_type?: string | null
  version?: number | null
}

export interface ActivityTimeline {
  date: string
  items: ActivityEntry[]
}

export interface ActivityBindingContext {
  mode?: 'feed' | 'audit' | 'history' | 'compact' | 'timeline'
  entity_type?: ActivityEntityType | string
  entity_public_id?: string
  severity_filter?: ActivitySeverity | string
  action_filter?: string
  actor_filter?: string
  per_page?: number
  show_filters?: boolean
  show_search?: boolean
  show_details?: boolean
}

function normalizeActor(raw: unknown): ActivityActor | null {
  if (!raw) return null
  const data = asRecord(raw)
  return {
    type: asString(data.type) || null,
    user_public_id: asString(data.user_public_id ?? data.userPublicId) || null,
    membership_public_id: asString(data.membership_public_id ?? data.membershipPublicId) || null,
    display_name: asString(data.display_name ?? data.displayName ?? data.name) || null,
    email: asString(data.email) || null,
  }
}

function normalizeTarget(raw: unknown): ActivityTarget | null {
  if (!raw) return null
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  return {
    type: normalizeEntityType(asString(data.type ?? data.entity_type ?? data.entityType ?? metadata.entity_type)),
    public_id: asString(data.public_id ?? data.publicId ?? data.entity_public_id ?? data.entityPublicId) || null,
    label: asString(data.label ?? data.name ?? data.display_name ?? data.displayName) || null,
    module_key: asString(data.module_key ?? data.moduleKey ?? metadata.module_key) || null,
    entity_key: asString(data.entity_key ?? data.entityKey ?? metadata.entity_key) || null,
    route: typeof (data.route ?? metadata.route) === 'string' ? ((data.route ?? metadata.route) as string) : null,
  }
}

function normalizeChangeSet(raw: unknown): ActivityChangeSet[] {
  const data = asRecord(raw)
  const before = data.before ?? data.before_state ?? data.beforeState
  const after = data.after ?? data.after_state ?? data.afterState

  if (Array.isArray(raw)) {
    return raw.map((entry) => {
      const item = asRecord(entry)
      return {
        field: asString(item.field ?? item.key) || null,
        before: item.before ?? item.before_value ?? item.beforeValue,
        after: item.after ?? item.after_value ?? item.afterValue,
        change_type: asString(item.change_type ?? item.changeType) || null,
        sensitive: asBoolean(item.sensitive ?? item.redacted, false),
      }
    })
  }

  if (before || after) {
    const beforeRecord = asRecord(before)
    const afterRecord = asRecord(after)
    const keys = new Set([...Object.keys(beforeRecord), ...Object.keys(afterRecord)])
    return Array.from(keys).map((field) => ({
      field,
      before: beforeRecord[field],
      after: afterRecord[field],
      change_type: beforeRecord[field] === undefined ? 'added' : afterRecord[field] === undefined ? 'removed' : 'updated',
      sensitive: field.toLowerCase().includes('password') || field.toLowerCase().includes('secret') || field.toLowerCase().includes('token'),
    }))
  }

  return []
}

export function normalizeEntityType(value?: string | null): ActivityEntityType {
  const normalized = asString(value, 'custom').toLowerCase()
  const known: ActivityEntityType[] = [
    'document', 'workflow', 'task', 'approval', 'report', 'dashboard', 'form', 'table',
    'notification', 'record', 'user', 'workspace', 'security', 'system', 'custom',
  ]
  return known.includes(normalized as ActivityEntityType) ? (normalized as ActivityEntityType) : normalized
}

export function normalizeSeverity(value?: string | null): ActivitySeverity {
  const normalized = asString(value, 'info').toLowerCase()
  if (['critical', 'high', 'warning', 'medium', 'low', 'info'].includes(normalized)) {
    return normalized as ActivitySeverity
  }
  return 'info'
}

export function normalizeActivityEntry(raw: unknown, source: ActivitySource = 'backend'): ActivityEntry {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  const context = asRecord(data.context)
  const entity = normalizeTarget(data.entity ?? data.target ?? {
    type: data.entity_type ?? data.entityType,
    public_id: data.entity_public_id ?? data.entityPublicId,
    label: data.entity_label ?? data.entityLabel ?? metadata.entity_label,
    module_key: data.module_key ?? data.moduleKey ?? metadata.module_key,
    entity_key: data.entity_key ?? data.entityKey ?? metadata.entity_key,
  })

  return {
    public_id: asString(data.public_id ?? data.publicId ?? data.id ?? `${asString(data.action)}-${asString(data.occurred_at ?? data.created_at)}`),
    occurred_at: typeof (data.occurred_at ?? data.occurredAt ?? data.created_at ?? data.createdAt ?? data.timestamp) === 'string'
      ? ((data.occurred_at ?? data.occurredAt ?? data.created_at ?? data.createdAt ?? data.timestamp) as string)
      : null,
    action: asString(data.action ?? data.event_type ?? data.eventType ?? data.change_type ?? data.changeType, 'updated'),
    summary: asString(data.summary ?? data.description ?? data.message ?? data.title) || null,
    severity: normalizeSeverity(asString(data.severity ?? metadata.severity)),
    category: asString(data.category ?? metadata.category) || null,
    actor: normalizeActor(data.actor ?? data.changed_by ?? data.changedBy),
    entity,
    changes: normalizeChangeSet(data.changes ?? { before: data.before_state ?? data.beforeState ?? data.before_value, after: data.after_state ?? data.afterState ?? data.after_value }),
    metadata,
    context,
    module_key: asString(data.module_key ?? data.moduleKey ?? entity?.module_key ?? metadata.module_key) || null,
    workspace_public_id: asString(data.workspace_public_id ?? data.workspacePublicId) || null,
    organization_public_id: asString(data.organization_public_id ?? data.organizationPublicId) || null,
    source: (asString(data.source) as ActivitySource) || source,
    permission: asString(data.permission ?? metadata.permission ?? metadata.required_permission) || null,
  }
}

export function normalizeAuditEntry(raw: unknown, source: ActivitySource = 'backend'): AuditEntry {
  const entry = normalizeActivityEntry(raw, source)
  const data = asRecord(raw)
  const context = asRecord(data.context ?? entry.context)
  return {
    ...entry,
    event_version: typeof data.event_version === 'number' ? data.event_version : undefined,
    retention_class: asString(data.retention_class ?? data.retentionClass) || null,
    expires_at: typeof (data.expires_at ?? data.expiresAt) === 'string' ? ((data.expires_at ?? data.expiresAt) as string) : null,
    ip_address: asString(context.ip_address ?? context.ipAddress ?? data.ip_address ?? data.ipAddress) || null,
    user_agent: asString(context.user_agent ?? context.userAgent ?? data.user_agent ?? data.userAgent) || null,
    request_id: asString(context.request_id ?? context.requestId ?? data.request_id ?? data.requestId) || null,
  }
}

export function normalizeHistoryEntry(raw: unknown, source: ActivitySource = 'backend'): HistoryEntry {
  const entry = normalizeActivityEntry(raw, source)
  const data = asRecord(raw)
  return {
    ...entry,
    change_type: asString(data.change_type ?? data.changeType ?? entry.action) || null,
    version: typeof (data.version ?? data.event_version) === 'number' ? ((data.version ?? data.event_version) as number) : null,
  }
}

export function normalizeActivityQueryResult(raw: unknown, fallback: Partial<ActivityQueryResult> = {}): ActivityQueryResult {
  const data = asRecord(raw)
  const items = asArray(data.items ?? data.data ?? data.results ?? data.events ?? data.logs).map((item) =>
    normalizeActivityEntry(item, fallback.source ?? 'backend'),
  )
  const meta = asRecord(data.meta)
  return {
    items,
    page: asNumber(data.page ?? meta.page, fallback.page ?? 1),
    per_page: asNumber(data.per_page ?? data.perPage ?? meta.per_page ?? meta.perPage, fallback.per_page ?? 25),
    total: asNumber(data.total ?? meta.total, items.length),
    has_more: asBoolean(data.has_more ?? data.hasMore ?? meta.has_more ?? meta.hasMore, false),
    next_cursor: asString(meta.next_cursor ?? meta.nextCursor) || null,
    source: fallback.source ?? 'backend',
  }
}

export function normalizeAuditSummary(raw: unknown): MetadataRecord {
  const data = asRecord(raw)
  return asRecord(data.data ?? data)
}

export function normalizeActivityBindingContext(raw: MetadataRecord | undefined): ActivityBindingContext {
  const config = asRecord(raw)
  return {
    mode: ['feed', 'audit', 'history', 'compact', 'timeline'].includes(asString(config.mode))
      ? (asString(config.mode) as ActivityBindingContext['mode'])
      : 'feed',
    entity_type: normalizeEntityType(asString(config.entity_type ?? config.entityType)),
    entity_public_id: asString(config.entity_public_id ?? config.entityPublicId),
    severity_filter: asString(config.severity_filter ?? config.severityFilter),
    action_filter: asString(config.action_filter ?? config.actionFilter),
    actor_filter: asString(config.actor_filter ?? config.actorFilter),
    per_page: asNumber(config.per_page ?? config.perPage, 25) || 25,
    show_filters: config.show_filters !== false && config.showFilters !== false,
    show_search: config.show_search !== false && config.showSearch !== false,
    show_details: config.show_details !== false && config.showDetails !== false,
  }
}

export function buildActivityQueryRequest(payload: ActivityQueryPayload = {}): Record<string, unknown> {
  return {
    page: payload.page ?? 1,
    per_page: payload.per_page ?? 25,
    search: payload.search ?? '',
    filters: payload.filters,
    sorts: payload.sorts,
    entity_type: payload.entity_type,
    entity_public_id: payload.entity_public_id,
    workspace_public_id: payload.workspace_public_id,
    actor_user_public_id: payload.actor_user_public_id,
    actor_membership_public_id: payload.actor_membership_public_id,
    action: payload.action,
    category: payload.category,
    severity: payload.severity,
    occurred_from: payload.occurred_from,
    occurred_to: payload.occurred_to,
    cursor: payload.cursor,
    metadata: payload.metadata ?? { source: 'web' },
  }
}
