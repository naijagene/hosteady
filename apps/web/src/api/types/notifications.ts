import { asArray, asBoolean, asNumber, asRecord, asString, type MetadataRecord } from './metadata-common'

export type NotificationCategory =
  | 'all'
  | 'unread'
  | 'announcement'
  | 'mention'
  | 'reminder'
  | 'workflow'
  | 'document'
  | 'report'
  | 'dashboard'
  | 'system'

export interface NotificationAction {
  action_key: string
  label: string
  route?: string | null
  metadata?: MetadataRecord
}

export interface NotificationAttachment {
  public_id?: string
  title?: string
  mime_type?: string | null
  url?: string | null
  metadata?: MetadataRecord
}

export interface Notification {
  public_id: string
  title: string
  body: string
  status?: string
  priority?: string
  category?: NotificationCategory | string
  scope?: string
  channels?: string[]
  read_at?: string | null
  created_at?: string | null
  sender?: string | null
  recipient?: string | null
  template_key?: string | null
  merge_data?: MetadataRecord
  metadata?: MetadataRecord
  actions?: NotificationAction[]
  attachments?: NotificationAttachment[]
}

export interface Announcement extends Notification {
  announcement_type?: string | null
}

export interface Reminder extends Notification {
  due_at?: string | null
}

export interface Mention extends Notification {
  mentioned_by?: string | null
}

export interface NotificationPreference {
  public_id?: string
  channel?: string
  type?: string
  enabled?: boolean
  preferred_channels?: string[]
  digest_frequency?: string | null
  quiet_hours?: MetadataRecord
  metadata?: MetadataRecord
}

export interface NotificationFilter {
  key: string
  value: string
}

export interface NotificationQueryPayload {
  page?: number
  per_page?: number
  search?: string
  status?: string
  category?: NotificationCategory | string
  unread_only?: boolean
  sort_key?: string
  sort_direction?: 'asc' | 'desc'
  filters?: NotificationFilter[]
  metadata?: MetadataRecord
}

export interface NotificationQueryResult {
  items: Notification[]
  page: number
  per_page: number
  total: number
  has_more: boolean
}

export interface NotificationCenterState {
  activeTab: NotificationCategory | 'unread'
  search: string
  selectedPublicId?: string | null
}

export interface NotificationBindingContext {
  mode?: 'full' | 'compact' | 'dropdown' | 'announcements' | 'mentions' | 'reminders' | 'workflow' | 'documents' | 'reports'
  category?: NotificationCategory | string
  show_counts?: boolean
  actions_enabled?: boolean
  delete_enabled?: boolean
  per_page?: number
  empty_state_message?: string
}

function normalizeAction(raw: unknown): NotificationAction {
  const data = asRecord(raw)
  return {
    action_key: asString(data.action_key ?? data.actionKey ?? data.key),
    label: asString(data.label ?? data.title, 'Open'),
    route: typeof (data.route ?? data.href) === 'string' ? ((data.route ?? data.href) as string) : null,
    metadata: asRecord(data.metadata),
  }
}

function normalizeAttachment(raw: unknown): NotificationAttachment {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    title: asString(data.title ?? data.name),
    mime_type: typeof (data.mime_type ?? data.mimeType) === 'string' ? ((data.mime_type ?? data.mimeType) as string) : null,
    url: typeof data.url === 'string' ? data.url : null,
    metadata: asRecord(data.metadata),
  }
}

export function inferNotificationCategory(notification: Notification): NotificationCategory | string {
  const metadata = notification.metadata ?? {}
  const explicit = asString(metadata.category ?? metadata.notification_category ?? metadata.type)
  if (explicit) {
    return explicit
  }

  const template = (notification.template_key ?? '').toLowerCase()
  if (template.includes('announcement') || notification.scope === 'broadcast') {
    return 'announcement'
  }
  if (template.includes('mention')) {
    return 'mention'
  }
  if (template.includes('reminder')) {
    return 'reminder'
  }
  if (
    template.includes('workflow') ||
    metadata.workflow_task_public_id ||
    metadata.task_public_id ||
    metadata.approval_public_id ||
    metadata.workflow_instance_public_id
  ) {
    return 'workflow'
  }
  if (metadata.document_public_id || template.includes('document')) {
    return 'document'
  }
  if (metadata.report_key || template.includes('report')) {
    return 'report'
  }
  if (metadata.dashboard_key || template.includes('dashboard')) {
    return 'dashboard'
  }

  return 'system'
}

export function normalizeNotification(raw: unknown): Notification {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  const mergeData = asRecord(data.merge_data ?? data.mergeData)
  const notification: Notification = {
    public_id: asString(data.public_id ?? data.publicId),
    title: asString(data.title, 'Notification'),
    body: asString(data.body ?? data.message ?? data.summary),
    status: asString(data.status),
    priority: asString(data.priority, 'normal'),
    scope: asString(data.scope),
    channels: asArray<string>(data.channels).map((item) => asString(item)),
    read_at: typeof (data.read_at ?? data.readAt) === 'string' ? ((data.read_at ?? data.readAt) as string) : null,
    created_at: typeof (data.created_at ?? data.createdAt) === 'string' ? ((data.created_at ?? data.createdAt) as string) : null,
    sender: typeof (metadata.sender ?? metadata.from ?? mergeData.sender) === 'string'
      ? ((metadata.sender ?? metadata.from ?? mergeData.sender) as string)
      : null,
    recipient: typeof (metadata.recipient ?? metadata.to ?? mergeData.recipient) === 'string'
      ? ((metadata.recipient ?? metadata.to ?? mergeData.recipient) as string)
      : null,
    template_key: typeof (data.template_key ?? data.templateKey) === 'string'
      ? ((data.template_key ?? data.templateKey) as string)
      : null,
    merge_data: mergeData,
    metadata,
    actions: asArray(data.actions).map(normalizeAction),
    attachments: asArray(data.attachments).map(normalizeAttachment),
  }

  notification.category = inferNotificationCategory(notification)
  return notification
}

export function normalizeAnnouncement(raw: unknown): Announcement {
  const notification = normalizeNotification(raw)
  const metadata = notification.metadata ?? {}
  return {
    ...notification,
    announcement_type: asString(metadata.announcement_type ?? metadata.announcementType) || null,
  }
}

export function normalizeReminder(raw: unknown): Reminder {
  const notification = normalizeNotification(raw)
  const metadata = notification.metadata ?? {}
  const dueAt = metadata.due_at ?? metadata.dueAt
  return {
    ...notification,
    due_at: typeof dueAt === 'string' ? dueAt : null,
  }
}

export function normalizeMention(raw: unknown): Mention {
  const notification = normalizeNotification(raw)
  const metadata = notification.metadata ?? {}
  return {
    ...notification,
    mentioned_by: typeof (metadata.mentioned_by ?? metadata.mentionedBy) === 'string'
      ? ((metadata.mentioned_by ?? metadata.mentionedBy) as string)
      : null,
  }
}

export function normalizeNotificationPreference(raw: unknown): NotificationPreference {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    channel: asString(data.channel),
    type: asString(data.type),
    enabled: asBoolean(data.enabled, true),
    preferred_channels: asArray<string>(data.preferred_channels ?? data.preferredChannels).map((item) => asString(item)),
    digest_frequency: typeof (data.digest_frequency ?? data.digestFrequency) === 'string'
      ? ((data.digest_frequency ?? data.digestFrequency) as string)
      : null,
    quiet_hours: asRecord(data.quiet_hours ?? data.quietHours),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeNotificationBindingContext(raw: MetadataRecord | undefined): NotificationBindingContext {
  const config = asRecord(raw)
  const mode = asString(config.mode)
  const allowedModes = ['full', 'compact', 'dropdown', 'announcements', 'mentions', 'reminders', 'workflow', 'documents', 'reports']
  return {
    mode: (allowedModes.includes(mode) ? mode : 'full') as NotificationBindingContext['mode'],
    category: asString(config.category ?? config.tab),
    show_counts: config.show_counts === true || config.showCounts === true,
    actions_enabled: config.actions_enabled !== false && config.actionsEnabled !== false,
    delete_enabled: config.delete_enabled === true || config.deleteEnabled === true,
    per_page: asNumber(config.per_page ?? config.perPage, 25) || 25,
    empty_state_message: asString(config.empty_state_message ?? config.emptyStateMessage),
  }
}

export function buildNotificationQueryRequest(payload: NotificationQueryPayload = {}): Record<string, unknown> {
  return {
    page: payload.page ?? 1,
    per_page: payload.per_page ?? 25,
    limit: payload.per_page ?? 25,
    search: payload.search ?? '',
    status: payload.status,
    category: payload.category,
    unread_only: payload.unread_only,
    sort_key: payload.sort_key,
    sort_direction: payload.sort_direction,
    metadata: payload.metadata ?? { source: 'web' },
  }
}
