import type { AxiosError } from 'axios'
import { apiClient } from '../client'
import { ApiError } from '../errors'
import { unwrapData } from '../unwrap'
import type { ApiErrorBody } from '../types/api'
import { asArray, type MetadataRecord } from '../types/metadata-common'
import {
  buildWorkflowQueryRequest,
  normalizeApproval,
  normalizeHumanTask,
  normalizeHumanTaskComment,
  normalizeHumanTaskHistory,
  normalizeWorkflowActionResult,
  normalizeWorkflowDefinition,
  normalizeWorkflowInstance,
  normalizeWorkflowInstanceHistory,
  type Approval,
  type ApprovalDecisionPayload,
  type HumanTask,
  type HumanTaskComment,
  type HumanTaskHistory,
  type TaskCompletionPayload,
  type WorkflowActionResult,
  type WorkflowDefinition,
  type WorkflowInstance,
  type WorkflowInstanceHistory,
  type WorkflowQueryPayload,
} from '../types/workflows'

export async function fetchWorkflowDefinitions(): Promise<WorkflowDefinition[]> {
  const response = await apiClient.get<
    WorkflowDefinition[] | { data: WorkflowDefinition[] } | { data: unknown[] }
  >('tenant/workflows/definitions')

  return asArray(unwrapData(response.data)).map(normalizeWorkflowDefinition)
}

export async function fetchWorkflowDefinition(publicId: string): Promise<WorkflowDefinition> {
  try {
    const response = await apiClient.get<WorkflowDefinition | { data: WorkflowDefinition }>(
      `tenant/workflows/definitions/${encodeURIComponent(publicId)}`,
    )
    return normalizeWorkflowDefinition(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function executeWorkflowDefinition(
  publicId: string,
  payload: Record<string, unknown> = {},
): Promise<WorkflowActionResult> {
  try {
    const response = await apiClient.post<WorkflowActionResult | { data: WorkflowActionResult }>(
      `tenant/workflows/definitions/${encodeURIComponent(publicId)}/execute`,
      payload,
    )
    return normalizeWorkflowActionResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchWorkflowInstances(payload: WorkflowQueryPayload = {}): Promise<WorkflowInstance[]> {
  try {
    const response = await apiClient.get<
      WorkflowInstance[] | { data: WorkflowInstance[] } | { data: unknown[] }
    >('tenant/workflow-instances', { params: buildWorkflowQueryRequest(payload) })

    return asArray(unwrapData(response.data)).map(normalizeWorkflowInstance)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchWorkflowInstance(publicId: string): Promise<WorkflowInstance> {
  try {
    const response = await apiClient.get<WorkflowInstance | { data: WorkflowInstance }>(
      `tenant/workflow-instances/${encodeURIComponent(publicId)}`,
    )
    return normalizeWorkflowInstance(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchWorkflowInstanceHistory(publicId: string): Promise<WorkflowInstanceHistory> {
  try {
    const response = await apiClient.get<WorkflowInstanceHistory | { data: WorkflowInstanceHistory }>(
      `tenant/workflow-instances/${encodeURIComponent(publicId)}/history`,
    )
    return normalizeWorkflowInstanceHistory(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function cancelWorkflowInstance(publicId: string): Promise<WorkflowInstance> {
  try {
    const response = await apiClient.post<WorkflowInstance | { data: WorkflowInstance }>(
      `tenant/workflow-instances/${encodeURIComponent(publicId)}/cancel`,
    )
    return normalizeWorkflowInstance(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function resumeWorkflowInstance(publicId: string): Promise<WorkflowActionResult> {
  try {
    const response = await apiClient.post<WorkflowActionResult | { data: WorkflowActionResult }>(
      `tenant/workflow-instances/${encodeURIComponent(publicId)}/resume`,
    )
    return normalizeWorkflowActionResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchHumanTasks(payload: WorkflowQueryPayload = {}): Promise<HumanTask[]> {
  try {
    const response = await apiClient.get<HumanTask[] | { data: HumanTask[] } | { data: unknown[] }>(
      'tenant/human-tasks',
      { params: buildWorkflowQueryRequest(payload) },
    )
    return asArray(unwrapData(response.data)).map(normalizeHumanTask)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchHumanTaskInbox(type: string, limit = 50): Promise<HumanTask[]> {
  try {
    const response = await apiClient.get<HumanTask[] | { data: HumanTask[] } | { data: unknown[] }>(
      'tenant/human-tasks/inbox',
      { params: { type, limit } },
    )
    return asArray(unwrapData(response.data)).map(normalizeHumanTask)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchHumanTask(publicId: string): Promise<HumanTask> {
  try {
    const response = await apiClient.get<HumanTask | { data: HumanTask }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}`,
    )
    return normalizeHumanTask(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function openHumanTask(publicId: string): Promise<HumanTask> {
  try {
    const response = await apiClient.post<HumanTask | { data: HumanTask }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}/open`,
    )
    return normalizeHumanTask(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function completeHumanTask(
  publicId: string,
  payload: TaskCompletionPayload = {},
): Promise<HumanTask> {
  try {
    const response = await apiClient.post<HumanTask | { data: HumanTask }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}/complete`,
      payload,
    )
    return normalizeHumanTask(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function cancelHumanTask(publicId: string): Promise<HumanTask> {
  try {
    const response = await apiClient.post<HumanTask | { data: HumanTask }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}/cancel`,
    )
    return normalizeHumanTask(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchHumanTaskComments(publicId: string): Promise<HumanTaskComment[]> {
  try {
    const response = await apiClient.get<HumanTaskComment[] | { data: HumanTaskComment[] }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}/comments`,
    )
    return asArray(unwrapData(response.data)).map(normalizeHumanTaskComment)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function addHumanTaskComment(publicId: string, body: string): Promise<HumanTaskComment> {
  try {
    const response = await apiClient.post<HumanTaskComment | { data: HumanTaskComment }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}/comments`,
      { body },
    )
    return normalizeHumanTaskComment(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchHumanTaskHistory(publicId: string): Promise<HumanTaskHistory[]> {
  try {
    const response = await apiClient.get<HumanTaskHistory[] | { data: HumanTaskHistory[] }>(
      `tenant/human-tasks/${encodeURIComponent(publicId)}/history`,
    )
    return asArray(unwrapData(response.data)).map(normalizeHumanTaskHistory)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchApprovals(payload: WorkflowQueryPayload = {}): Promise<Approval[]> {
  try {
    const response = await apiClient.get<Approval[] | { data: Approval[] } | { data: unknown[] }>(
      'tenant/approvals',
      { params: buildWorkflowQueryRequest(payload) },
    )
    return asArray(unwrapData(response.data)).map(normalizeApproval)
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchApproval(publicId: string): Promise<Approval> {
  try {
    const response = await apiClient.get<Approval | { data: Approval }>(
      `tenant/approvals/${encodeURIComponent(publicId)}`,
    )
    return normalizeApproval(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function approveRequest(
  publicId: string,
  payload: ApprovalDecisionPayload = {},
): Promise<WorkflowActionResult> {
  try {
    const response = await apiClient.post<WorkflowActionResult | { data: WorkflowActionResult }>(
      `tenant/approvals/${encodeURIComponent(publicId)}/approve`,
      payload,
    )
    return normalizeWorkflowActionResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function rejectRequest(
  publicId: string,
  payload: ApprovalDecisionPayload = {},
): Promise<WorkflowActionResult> {
  try {
    const response = await apiClient.post<WorkflowActionResult | { data: WorkflowActionResult }>(
      `tenant/approvals/${encodeURIComponent(publicId)}/reject`,
      payload,
    )
    return normalizeWorkflowActionResult(unwrapData(response.data))
  } catch (error) {
    throw ApiError.fromAxios(error as AxiosError<ApiErrorBody>)
  }
}

export async function fetchHumanTaskStatistics(): Promise<MetadataRecord> {
  try {
    const response = await apiClient.get<{ data: MetadataRecord } | MetadataRecord>(
      'tenant/human-tasks/statistics',
    )
    return unwrapData(response.data) as MetadataRecord
  } catch {
    return {}
  }
}