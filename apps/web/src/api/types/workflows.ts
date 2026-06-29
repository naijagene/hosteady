import { asArray, asRecord, asString, type MetadataRecord } from './metadata-common'

export interface WorkflowDefinition {
  public_id: string
  workflow_key?: string
  name: string
  definition_key?: string
  status?: string
  description?: string | null
  module_key?: string | null
  metadata?: MetadataRecord
  created_at?: string | null
  updated_at?: string | null
}

export interface WorkflowVersion {
  public_id: string
  version_number?: number
  status?: string
  metadata?: MetadataRecord
}

export interface WorkflowInstance {
  public_id: string
  definition_public_id?: string
  definition_name?: string
  workflow_key?: string | null
  status?: string
  current_node_id?: string | null
  started_at?: string | null
  completed_at?: string | null
  duration_ms?: number | null
  warnings?: string[]
  errors?: string[]
  metadata?: MetadataRecord
  created_at?: string | null
}

export interface WorkflowInstanceHistory {
  steps?: WorkflowExecutionStep[]
  events?: WorkflowExecutionEvent[]
  logs?: WorkflowExecutionLog[]
  metadata?: MetadataRecord
}

export interface WorkflowExecutionStep {
  public_id?: string
  node_id?: string
  node_type?: string
  status?: string
  started_at?: string | null
  completed_at?: string | null
  duration_ms?: number | null
  metadata?: MetadataRecord
}

export interface WorkflowExecutionEvent {
  event_type: string
  occurred_at: string
  summary?: string | null
  metadata?: MetadataRecord
}

export interface WorkflowExecutionLog {
  level?: string
  message: string
  occurred_at?: string | null
  metadata?: MetadataRecord
}

export interface HumanTask {
  public_id: string
  title: string
  status?: string
  task_type?: string
  description?: string | null
  priority?: string | null
  workflow_instance_public_id?: string | null
  workflow_definition_name?: string | null
  assignee_user_public_id?: string | null
  assignee_role_key?: string | null
  due_at?: string | null
  created_at?: string | null
  opened_at?: string | null
  completed_at?: string | null
  comments_count?: number
  metadata?: MetadataRecord
}

export interface HumanTaskComment {
  public_id: string
  body: string
  author_user_public_id?: string | null
  created_at?: string | null
  metadata?: MetadataRecord
}

export interface HumanTaskHistory {
  event_type: string
  occurred_at: string
  summary?: string | null
  metadata?: MetadataRecord
}

export interface TaskAssignment {
  assignee_user_public_id?: string | null
  assignee_role_key?: string | null
  assigned_at?: string | null
}

export interface Approval {
  public_id: string
  title: string
  status?: string
  approval_type?: string | null
  task_public_id?: string | null
  workflow_instance_public_id?: string | null
  workflow_definition_name?: string | null
  requested_at?: string | null
  decided_at?: string | null
  decision_type?: string | null
  metadata?: MetadataRecord
}

export interface ApprovalDecision {
  decision_type: string
  comment?: string | null
  decided_at?: string | null
  metadata?: MetadataRecord
}

export interface WorkflowInboxItem {
  item_type: 'task' | 'approval' | 'instance'
  public_id: string
  title: string
  status?: string
  due_at?: string | null
  created_at?: string | null
  metadata?: MetadataRecord
}

export interface WorkflowActionResult {
  status?: string
  public_id?: string
  metadata?: MetadataRecord
  error?: string | null
}

export interface WorkflowBindingContext {
  mode?: 'inbox' | 'approvals' | 'instances' | 'definitions' | 'compact'
  inbox_type?: string
  status_filter?: string
  task_type_filter?: string
  show_counts?: boolean
  actions_enabled?: boolean
  comments_enabled?: boolean
  per_page?: number
  empty_state_message?: string
}

export interface WorkflowQueryPayload {
  page?: number
  per_page?: number
  search?: string
  status?: string
  task_type?: string
  inbox_type?: string
  metadata?: MetadataRecord
}

export interface ApprovalDecisionPayload {
  comment?: string
  metadata?: MetadataRecord
}

export interface TaskCompletionPayload {
  result?: MetadataRecord
  metadata?: MetadataRecord
}

function normalizeExecutionStep(raw: unknown): WorkflowExecutionStep {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    node_id: asString(data.node_id ?? data.nodeId),
    node_type: asString(data.node_type ?? data.nodeType),
    status: asString(data.status),
    started_at: typeof (data.started_at ?? data.startedAt) === 'string' ? ((data.started_at ?? data.startedAt) as string) : null,
    completed_at: typeof (data.completed_at ?? data.completedAt) === 'string' ? ((data.completed_at ?? data.completedAt) as string) : null,
    duration_ms: typeof (data.duration_ms ?? data.durationMs) === 'number' ? ((data.duration_ms ?? data.durationMs) as number) : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeWorkflowDefinition(raw: unknown): WorkflowDefinition {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    workflow_key: asString(data.workflow_key ?? data.workflowKey ?? data.definition_key ?? data.definitionKey),
    name: asString(data.name, 'Workflow'),
    definition_key: asString(data.definition_key ?? data.definitionKey ?? data.workflow_key ?? data.workflowKey),
    status: asString(data.status),
    description: typeof data.description === 'string' ? data.description : null,
    module_key: typeof (data.module_key ?? data.moduleKey) === 'string' ? ((data.module_key ?? data.moduleKey) as string) : null,
    metadata: asRecord(data.metadata),
    created_at: typeof (data.created_at ?? data.createdAt) === 'string' ? ((data.created_at ?? data.createdAt) as string) : null,
    updated_at: typeof (data.updated_at ?? data.updatedAt) === 'string' ? ((data.updated_at ?? data.updatedAt) as string) : null,
  }
}

export function normalizeWorkflowInstance(raw: unknown): WorkflowInstance {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    definition_public_id: asString(data.definition_public_id ?? data.definitionPublicId),
    definition_name: asString(data.definition_name ?? data.definitionName ?? metadata.definition_name),
    workflow_key: typeof (data.workflow_key ?? data.workflowKey) === 'string' ? ((data.workflow_key ?? data.workflowKey) as string) : null,
    status: asString(data.status),
    current_node_id: typeof (data.current_node_id ?? data.currentNodeId) === 'string' ? ((data.current_node_id ?? data.currentNodeId) as string) : null,
    started_at: typeof (data.started_at ?? data.startedAt) === 'string' ? ((data.started_at ?? data.startedAt) as string) : null,
    completed_at: typeof (data.completed_at ?? data.completedAt) === 'string' ? ((data.completed_at ?? data.completedAt) as string) : null,
    duration_ms: typeof (data.duration_ms ?? data.durationMs) === 'number' ? ((data.duration_ms ?? data.durationMs) as number) : null,
    warnings: asArray<string>(data.warnings).map((item) => asString(item)),
    errors: asArray<string>(data.errors).map((item) => asString(item)),
    metadata,
    created_at: typeof (data.created_at ?? data.createdAt) === 'string' ? ((data.created_at ?? data.createdAt) as string) : null,
  }
}

export function normalizeHumanTask(raw: unknown): HumanTask {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    title: asString(data.title ?? data.name, 'Task'),
    status: asString(data.status),
    task_type: asString(data.task_type ?? data.taskType ?? metadata.task_type, 'task'),
    description: typeof data.description === 'string' ? data.description : null,
    priority: typeof (data.priority ?? metadata.priority) === 'string' ? ((data.priority ?? metadata.priority) as string) : null,
    workflow_instance_public_id: asString(data.workflow_instance_public_id ?? data.workflowInstancePublicId),
    workflow_definition_name: asString(data.workflow_definition_name ?? data.workflowDefinitionName),
    assignee_user_public_id: asString(data.assignee_user_public_id ?? data.assigneeUserPublicId),
    assignee_role_key: asString(data.assignee_role_key ?? data.assigneeRoleKey),
    due_at: typeof (data.due_at ?? data.dueAt) === 'string' ? ((data.due_at ?? data.dueAt) as string) : null,
    created_at: typeof (data.created_at ?? data.createdAt) === 'string' ? ((data.created_at ?? data.createdAt) as string) : null,
    opened_at: typeof (data.opened_at ?? data.openedAt) === 'string' ? ((data.opened_at ?? data.openedAt) as string) : null,
    completed_at: typeof (data.completed_at ?? data.completedAt) === 'string' ? ((data.completed_at ?? data.completedAt) as string) : null,
    comments_count: typeof (data.comments_count ?? data.commentsCount ?? metadata.comments_count) === 'number'
      ? ((data.comments_count ?? data.commentsCount ?? metadata.comments_count) as number)
      : undefined,
    metadata,
  }
}

export function normalizeHumanTaskComment(raw: unknown): HumanTaskComment {
  const data = asRecord(raw)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    body: asString(data.body ?? data.comment, ''),
    author_user_public_id: asString(data.author_user_public_id ?? data.authorUserPublicId),
    created_at: typeof (data.created_at ?? data.createdAt) === 'string' ? ((data.created_at ?? data.createdAt) as string) : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeHumanTaskHistory(raw: unknown): HumanTaskHistory {
  const data = asRecord(raw)
  return {
    event_type: asString(data.event_type ?? data.eventType ?? data.type, 'event'),
    occurred_at: asString(data.occurred_at ?? data.occurredAt ?? data.created_at ?? data.createdAt),
    summary: typeof (data.summary ?? data.message) === 'string' ? ((data.summary ?? data.message) as string) : null,
    metadata: asRecord(data.metadata),
  }
}

export function normalizeApproval(raw: unknown): Approval {
  const data = asRecord(raw)
  const metadata = asRecord(data.metadata)
  return {
    public_id: asString(data.public_id ?? data.publicId),
    title: asString(data.title ?? data.name, 'Approval'),
    status: asString(data.status),
    approval_type: asString(data.approval_type ?? data.approvalType ?? data.decision_type ?? metadata.approval_type),
    task_public_id: asString(data.task_public_id ?? data.taskPublicId),
    workflow_instance_public_id: asString(data.workflow_instance_public_id ?? data.workflowInstancePublicId),
    workflow_definition_name: asString(data.workflow_definition_name ?? data.workflowDefinitionName),
    requested_at: typeof (data.requested_at ?? data.requestedAt ?? data.created_at ?? data.createdAt) === 'string'
      ? ((data.requested_at ?? data.requestedAt ?? data.created_at ?? data.createdAt) as string)
      : null,
    decided_at: typeof (data.decided_at ?? data.decidedAt) === 'string' ? ((data.decided_at ?? data.decidedAt) as string) : null,
    decision_type: asString(data.decision_type ?? data.decisionType),
    metadata,
  }
}

export function normalizeWorkflowActionResult(raw: unknown): WorkflowActionResult {
  const data = asRecord(raw)
  return {
    status: asString(data.status),
    public_id: asString(data.public_id ?? data.publicId),
    metadata: asRecord(data.metadata),
    error: typeof data.error === 'string' ? data.error : null,
  }
}

export function normalizeWorkflowInstanceHistory(raw: unknown): WorkflowInstanceHistory {
  const data = asRecord(raw)
  return {
    steps: asArray(data.steps ?? data.executions).map(normalizeExecutionStep),
    events: asArray(data.events).map((item) => {
      const event = asRecord(item)
      return {
        event_type: asString(event.event_type ?? event.eventType ?? event.type, 'event'),
        occurred_at: asString(event.occurred_at ?? event.occurredAt ?? event.created_at),
        summary: typeof event.summary === 'string' ? event.summary : null,
        metadata: asRecord(event.metadata),
      }
    }),
    logs: asArray(data.logs).map((item) => {
      const log = asRecord(item)
      return {
        level: asString(log.level),
        message: asString(log.message),
        occurred_at: typeof log.occurred_at === 'string' ? log.occurred_at : null,
        metadata: asRecord(log.metadata),
      }
    }),
    metadata: asRecord(data.metadata),
  }
}

export function normalizeWorkflowBindingContext(raw: MetadataRecord | undefined): WorkflowBindingContext {
  const config = asRecord(raw)
  const mode = asString(config.mode)
  return {
    mode: (['inbox', 'approvals', 'instances', 'definitions', 'compact'].includes(mode) ? mode : 'inbox') as WorkflowBindingContext['mode'],
    inbox_type: asString(config.inbox_type ?? config.inboxType, 'assigned') || 'assigned',
    status_filter: asString(config.status_filter ?? config.statusFilter),
    task_type_filter: asString(config.task_type_filter ?? config.taskTypeFilter),
    show_counts: config.show_counts === true || config.showCounts === true,
    actions_enabled: config.actions_enabled !== false && config.actionsEnabled !== false,
    comments_enabled: config.comments_enabled !== false && config.commentsEnabled !== false,
    per_page: typeof (config.per_page ?? config.perPage) === 'number' ? ((config.per_page ?? config.perPage) as number) : 25,
    empty_state_message: asString(config.empty_state_message ?? config.emptyStateMessage),
  }
}

export function buildWorkflowQueryRequest(payload: WorkflowQueryPayload = {}): Record<string, unknown> {
  return {
    page: payload.page ?? 1,
    per_page: payload.per_page ?? 25,
    limit: payload.per_page ?? 25,
    search: payload.search ?? '',
    status: payload.status,
    task_type: payload.task_type,
    type: payload.inbox_type,
    metadata: payload.metadata ?? { source: 'web' },
  }
}

export function toWorkflowInboxItemFromTask(task: HumanTask): WorkflowInboxItem {
  return {
    item_type: 'task',
    public_id: task.public_id,
    title: task.title,
    status: task.status,
    due_at: task.due_at,
    created_at: task.created_at,
    metadata: task.metadata,
  }
}

export function toWorkflowInboxItemFromApproval(approval: Approval): WorkflowInboxItem {
  return {
    item_type: 'approval',
    public_id: approval.public_id,
    title: approval.title,
    status: approval.status,
    created_at: approval.requested_at,
    metadata: approval.metadata,
  }
}

export function toWorkflowInboxItemFromInstance(instance: WorkflowInstance): WorkflowInboxItem {
  return {
    item_type: 'instance',
    public_id: instance.public_id,
    title: instance.definition_name ?? instance.public_id,
    status: instance.status,
    created_at: instance.started_at ?? instance.created_at,
    metadata: instance.metadata,
  }
}
