import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import { asArray } from '../types/metadata-common'
import {
  buildActivityQueryRequest,
  normalizeActivityEntry,
  normalizeActivityQueryResult,
  normalizeAuditEntry,
  normalizeAuditSummary,
  normalizeHistoryEntry,
  type ActivityEntry,
  type ActivityQueryPayload,
  type ActivityQueryResult,
  type ActivitySource,
  type AuditEntry,
  type HistoryEntry,
} from '../types/activity'

async function tryGet<T>(paths: string[], params?: Record<string, unknown>, mapper?: (raw: unknown) => T): Promise<T | null> {
  for (const path of paths) {
    try {
      const response = await apiClient.get(path, { params })
      return mapper ? mapper(unwrapData(response.data)) : (unwrapData(response.data) as T)
    } catch {
      // try next path
    }
  }
  return null
}

export async function fetchActivityFeed(payload: ActivityQueryPayload = {}): Promise<ActivityQueryResult> {
  const params = buildActivityQueryRequest(payload)
  try {
    const response = await apiClient.get('tenant/activity', { params })
    return normalizeActivityQueryResult(unwrapData(response.data), { source: 'backend' })
  } catch {
    try {
      const response = await apiClient.get('tenant/audit/events', { params })
      return normalizeActivityQueryResult(unwrapData(response.data), { source: 'backend' })
    } catch (error) {
      throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
    }
  }
}

export async function fetchActivityEntry(publicId: string): Promise<ActivityEntry> {
  const paths = [
    `tenant/activity/${encodeURIComponent(publicId)}`,
    `tenant/audit/events/${encodeURIComponent(publicId)}`,
    `tenant/audit/${encodeURIComponent(publicId)}`,
  ]

  for (const path of paths) {
    try {
      const response = await apiClient.get(path)
      return normalizeActivityEntry(unwrapData(response.data), 'backend')
    } catch {
      // continue
    }
  }

  throw new ApiError('Activity entry not found', { status: 404 })
}

export async function fetchAuditEvents(payload: ActivityQueryPayload = {}): Promise<ActivityQueryResult> {
  const params = buildActivityQueryRequest(payload)
  try {
    const response = await apiClient.get('tenant/audit/events', { params })
    const result = normalizeActivityQueryResult(unwrapData(response.data), { source: 'backend' })
    return { ...result, items: result.items.map((item) => normalizeAuditEntry(item, 'backend')) }
  } catch {
    try {
      const response = await apiClient.get('tenant/audit', { params })
      const result = normalizeActivityQueryResult(unwrapData(response.data), { source: 'backend' })
      return { ...result, items: result.items.map((item) => normalizeAuditEntry(item, 'backend')) }
    } catch (error) {
      throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
    }
  }
}

export async function fetchAuditEntry(publicId: string): Promise<AuditEntry> {
  const paths = [
    `tenant/audit/events/${encodeURIComponent(publicId)}`,
    `tenant/audit/${encodeURIComponent(publicId)}`,
  ]

  for (const path of paths) {
    try {
      const response = await apiClient.get(path)
      return normalizeAuditEntry(unwrapData(response.data), 'backend')
    } catch {
      // continue
    }
  }

  throw new ApiError('Audit entry not found', { status: 404 })
}

export async function fetchAuditSummary(payload: Pick<ActivityQueryPayload, 'occurred_from' | 'occurred_to' | 'workspace_public_id'> = {}) {
  try {
    const response = await apiClient.get('tenant/audit/summary', { params: buildActivityQueryRequest(payload) })
    return normalizeAuditSummary(unwrapData(response.data))
  } catch {
    return { total_events: 0, by_category: {}, by_severity: {}, recent_actions: [], top_actors: [] }
  }
}

export async function fetchSystemHistory(payload: ActivityQueryPayload = {}): Promise<ActivityQueryResult> {
  const params = buildActivityQueryRequest(payload)
  try {
    const response = await apiClient.get('tenant/history', { params })
    return normalizeActivityQueryResult(unwrapData(response.data), { source: 'backend' })
  } catch {
    return fetchAuditEvents({ ...payload, category: payload.category ?? 'system' })
  }
}

export async function fetchEntityHistory(
  entityType: string,
  entityPublicId: string,
  payload: ActivityQueryPayload = {},
): Promise<ActivityQueryResult> {
  const params = buildActivityQueryRequest({ ...payload, entity_type: entityType, entity_public_id: entityPublicId })
  const generic = await tryGet(
    [`tenant/history/${encodeURIComponent(entityType)}/${encodeURIComponent(entityPublicId)}`],
    params,
    (raw) => normalizeActivityQueryResult(raw, { source: 'backend' }),
  )
  if (generic) return generic

  const moduleKey = payload.metadata?.module_key ?? payload.metadata?.moduleKey
  const entityKey = payload.metadata?.entity_key ?? payload.metadata?.entityKey

  if (entityType === 'document') {
    const result = await tryGet([`tenant/documents/${encodeURIComponent(entityPublicId)}/activity`], params, (raw) =>
      normalizeActivityQueryResult({ data: asArray(raw) }, { source: 'backend' }),
    )
    if (result) return result
  }

  if (entityType === 'workflow') {
    const result = await tryGet([`tenant/workflow-instances/${encodeURIComponent(entityPublicId)}/history`], undefined, (raw) => {
      const data = asRecord(raw)
      const events = asArray(data.events ?? data.logs ?? data.steps ?? data)
      return normalizeActivityQueryResult({ data: events }, { source: 'workflow' })
    })
    if (result) return result
  }

  if (entityType === 'task') {
    const result = await tryGet([`tenant/human-tasks/${encodeURIComponent(entityPublicId)}/history`], undefined, (raw) =>
      normalizeActivityQueryResult({ data: asArray(raw) }, { source: 'workflow' }),
    )
    if (result) return result
  }

  if (moduleKey && entityKey && entityType === 'record') {
    const result = await tryGet(
      [`tenant/data/${encodeURIComponent(String(moduleKey))}/${encodeURIComponent(String(entityKey))}/${encodeURIComponent(entityPublicId)}/activity`],
      params,
      (raw) => normalizeActivityQueryResult({ data: asArray(raw) }, { source: 'backend' }),
    )
    if (result) return result
  }

  if (moduleKey && entityKey) {
    const result = await tryGet(
      [`tenant/entities/${encodeURIComponent(String(moduleKey))}/${encodeURIComponent(String(entityKey))}/${encodeURIComponent(entityPublicId)}/activity`],
      params,
      (raw) => normalizeActivityQueryResult({ data: asArray(raw) }, { source: 'backend' }),
    )
    if (result) return result
  }

  return { items: [], page: 1, per_page: payload.per_page ?? 25, total: 0, has_more: false, source: 'local' }
}

function asRecord(value: unknown): Record<string, unknown> {
  return value && typeof value === 'object' && !Array.isArray(value) ? (value as Record<string, unknown>) : {}
}

export async function fetchDocumentActivity(documentPublicId: string, payload: ActivityQueryPayload = {}): Promise<HistoryEntry[]> {
  try {
    const response = await apiClient.get(`tenant/documents/${encodeURIComponent(documentPublicId)}/activity`, {
      params: buildActivityQueryRequest(payload),
    })
    return asArray(unwrapData(response.data)).map((item) => normalizeHistoryEntry(item, 'document'))
  } catch {
    return []
  }
}

export async function safeFetchActivityFeed(payload: ActivityQueryPayload = {}): Promise<ActivityQueryResult> {
  try {
    return await fetchActivityFeed(payload)
  } catch {
    return { items: [], page: 1, per_page: payload.per_page ?? 25, total: 0, has_more: false, source: 'local' }
  }
}

export function labelActivitySource(source: ActivitySource): string {
  switch (source) {
    case 'backend':
      return 'Platform activity'
    case 'workflow':
      return 'Workflow history'
    case 'document':
      return 'Document activity'
    case 'runtime':
      return 'Runtime'
    default:
      return 'Local'
  }
}
